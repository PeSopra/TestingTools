#!/usr/bin/env python3
"""
Figma MCP Server - Custom implementation using Personal Access Tokens

This server replicates Figma's official MCP functionality but uses Personal Access Tokens,
enabling backend/headless operation without the desktop app requirement.
"""

from typing import Any, Optional
import os
import sys
import logging
import base64
import httpx
from mcp.server.fastmcp import FastMCP
from dotenv import load_dotenv
from html_generator import (
    create_smart_html_from_figma_data
)
from drupal_theme_generator import DrupalThemeGenerator

# Load environment variables
load_dotenv()

# Configure logging (IMPORTANT: Never use print() in MCP servers using stdio)
SERVER_VERSION = "1.0.6"

logging.basicConfig(
    level=logging.INFO,
    format=f'%(asctime)s [v{SERVER_VERSION}] - %(name)s - %(levelname)s - %(message)s',
    handlers=[logging.FileHandler(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'figma_mcp.log'))]
)
logger = logging.getLogger(__name__)
logger.info(f"Starting Figma MCP Server v{SERVER_VERSION}")

# Initialize FastMCP server
mcp = FastMCP("figma")

# Configuration
FIGMA_ACCESS_TOKEN = os.getenv("FIGMA_ACCESS_TOKEN")
FIGMA_API_BASE = "https://api.figma.com/v1"

if not FIGMA_ACCESS_TOKEN:
    logger.error("FIGMA_ACCESS_TOKEN not set in environment")
    raise ValueError("FIGMA_ACCESS_TOKEN environment variable is required")


async def make_figma_request(endpoint: str, params: dict | None = None) -> dict[str, Any] | None:
    """
    Make authenticated request to Figma REST API.
    
    Args:
        endpoint: API endpoint (e.g., "/files/{file_key}")
        params: Query parameters
        
    Returns:
        JSON response or None on error
    """
    url = f"{FIGMA_API_BASE}{endpoint}"
    headers = {
        "X-Figma-Token": FIGMA_ACCESS_TOKEN,
        "Accept": "application/json"
    }
    
    async with httpx.AsyncClient(timeout=30.0) as client:
        try:
            response = await client.get(url, headers=headers, params=params or {})
            response.raise_for_status()
            return response.json()
        except httpx.HTTPStatusError as e:
            logger.error(f"Figma API error: {e.response.status_code} - {e.response.text}")
            return None
        except Exception as e:
            logger.error(f"Request failed: {e}")
            return None


async def get_image_url(file_key: str, node_id: str, scale: float = 2.0, format: str = "png") -> str | None:
    """Get export URL for a Figma node image."""
    data = await make_figma_request(
        f"/images/{file_key}",
        params={"ids": node_id, "scale": scale, "format": format}
    )
    if data and "images" in data:
        node_key = node_id.replace(":", "-")
        return data["images"].get(node_key)
    return None


# Removed obsolete parse_node_to_code function - replaced by smarter HTML generation


# Removed obsolete get_metadata tool - replaced by fetch_node_data


# Removed obsolete get_design_context tool - replaced by generate_smart_html and generate_twig_template


# Removed obsolete get_screenshot tool - use export_images or analyze_images instead


# Removed obsolete create_design_system_rules tool - not actively used


@mcp.tool()
async def export_images(
    fileKey: str,
    nodeIds: str,
    outputDir: str = ".",
    scale: float = 2.0,
    format: str = "png"
) -> str:
    """
    Export images from Figma nodes to local files.
    
    Args:
        fileKey: Figma file key
        nodeIds: Comma-separated list of node IDs to export (e.g., "606:10,606:12")
        outputDir: Output directory path (default: current directory)
        scale: Export scale (1.0, 2.0, 3.0, 4.0) - default 2.0 for retina
        format: Image format (png, jpg, svg, pdf) - default png
        
    Returns:
        Status message with download results
    """
    logger.info(f"export_images called: fileKey={fileKey}, nodeIds={nodeIds}, outputDir={outputDir}")
    
    # Validate and filter node IDs
    node_list = [n.strip() for n in nodeIds.split(",")]
    
    # Filter out invalid node IDs (instance IDs with semicolons are not exportable)
    valid_node_ids = []
    invalid_node_ids = []
    
    for node_id in node_list:
        # Instance node IDs contain semicolons (e.g., I1-220;2-156) and are not directly exportable
        if ";" in node_id or node_id.startswith("I"):
            invalid_node_ids.append(node_id)
            logger.warning(f"Skipping instance node ID: {node_id} (not directly exportable)")
        else:
            valid_node_ids.append(node_id)
    
    if not valid_node_ids:
        error_msg = f"Error: No valid node IDs to export. "
        if invalid_node_ids:
            error_msg += f"Found {len(invalid_node_ids)} instance node IDs which cannot be directly exported: {', '.join(invalid_node_ids)}. "
        error_msg += "Instance node IDs (starting with 'I' or containing ';') must be converted to their base component/frame IDs."
        return error_msg
    
    # Join valid node IDs back for the API call
    valid_nodeIds_str = ",".join(valid_node_ids)
    
    # Get image URLs from Figma
    data = await make_figma_request(
        f"/images/{fileKey}",
        params={"ids": valid_nodeIds_str, "scale": scale, "format": format}
    )
    
    if not data:
        return "Error: Failed to fetch image URLs from Figma"
    
    if data.get("err"):
        return f"Error: Figma API returned error: {data.get('err')}"
    
    image_urls = data.get("images", {})
    
    if not image_urls:
        return f"Error: No images returned. Check that node IDs are correct and exportable."
    
    # Create output directory if it doesn't exist
    import os
    
    # Convert to absolute path if relative
    if not os.path.isabs(outputDir):
        outputDir = os.path.abspath(outputDir)
        logger.info(f"Converted to absolute path: {outputDir}")
    
    os.makedirs(outputDir, exist_ok=True)
    logger.info(f"Created/verified output directory: {outputDir}")
    
    # Download each image
    results = []
    if invalid_node_ids:
        results.append(f"⚠️  Skipped {len(invalid_node_ids)} instance/component override IDs (not directly exportable)")
    
    async with httpx.AsyncClient(timeout=60.0) as client:
        for i, node_id in enumerate(valid_node_ids):
            # Try both formats: "601:9" and "601-9"
            node_key = node_id.replace(":", "-")
            node_key_alt = node_id.replace("-", ":")
            
            image_url = image_urls.get(node_key) or image_urls.get(node_key_alt) or image_urls.get(node_id)
            
            if not image_url:
                results.append(f"⚠️  Node {node_id}: No URL found")
                continue
            
            try:
                # Download image
                response = await client.get(image_url)
                response.raise_for_status()
                
                # Save to file
                filename = f"node-{node_key}.{format}"
                filepath = os.path.join(outputDir, filename)
                
                with open(filepath, 'wb') as f:
                    f.write(response.content)
                
                size_kb = len(response.content) / 1024
                results.append(f"✓ Node {node_id}: Saved to {filepath} ({size_kb:.1f} KB)")
                
            except Exception as e:
                results.append(f"❌ Node {node_id}: Download failed - {str(e)}")
    
    summary = f"""
Image Export Results:
- Requested: {len(node_list)} nodes
- Output directory: {outputDir}
- Scale: {scale}x
- Format: {format}

Results:
{chr(10).join(results)}
"""
    
    return summary


@mcp.tool()
async def whoami() -> str:
    """
    Get authenticated user information.
    
    Returns:
        User email, ID, handle
    """
    logger.info("whoami called")
    
    data = await make_figma_request("/me")
    
    if not data:
        return "Error: Failed to fetch user information"
    
    return f"""
User Information:
- Email: {data.get('email', 'N/A')}
- ID: {data.get('id', 'N/A')}
- Handle: {data.get('handle', 'N/A')}
"""


@mcp.tool()
async def fetch_node_data(fileKey: str, nodeId: str) -> str:
    """
    Fetch complete node structure from Figma design.
    Returns detailed JSON data about the node and its children.
    
    Args:
        fileKey: Figma file key (e.g., "vOCOd0Kqmf9tN4FZ6yKcz9")
        nodeId: Node ID to fetch (e.g., "1:182" or "1-182")
    
    Returns:
        JSON structure with node hierarchy, dimensions, and properties
    """
    logger.info(f"fetch_node_data: {fileKey}, node: {nodeId}")
    
    # Normalize node ID (convert colon to dash)
    normalized_node_id = nodeId.replace(":", "-")
    
    data = await make_figma_request(
        f"/files/{fileKey}/nodes",
        params={"ids": nodeId}
    )
    
    if not data:
        return "Error: Failed to fetch node data"
    
    import json
    return json.dumps(data, indent=2)


@mcp.tool()
async def analyze_images(fileKey: str, nodeId: str) -> str:
    """
    Analyze a Figma node to find all image nodes.
    Returns a list of image node IDs and their names.
    
    Args:
        fileKey: Figma file key
        nodeId: Root node ID to analyze
    
    Returns:
        Summary of all image nodes found
    """
    logger.info(f"analyze_images: {fileKey}, node: {nodeId}")
    
    # Fetch node data
    data = await make_figma_request(
        f"/files/{fileKey}/nodes",
        params={"ids": nodeId}
    )
    
    if not data or "nodes" not in data:
        return "Error: Failed to fetch node data"
    
    # Recursively find image nodes
    image_nodes = []
    
    def traverse(node, path=""):
        if not node:
            return
        
        node_type = node.get("type", "")
        node_id = node.get("id", "")
        node_name = node.get("name", "Unnamed")
        
        # Check if it's an image node
        if node_type in ["RECTANGLE", "ELLIPSE", "VECTOR"] and node.get("fills"):
            for fill in node.get("fills", []):
                if fill.get("type") == "IMAGE":
                    image_nodes.append({
                        "id": node_id,
                        "name": node_name,
                        "type": node_type,
                        "path": path
                    })
                    break
        
        # Check children
        if "children" in node:
            for child in node["children"]:
                traverse(child, f"{path}/{node_name}")
    
    # Start traversal
    for node_key, node_data in data.get("nodes", {}).items():
        if node_data and "document" in node_data:
            traverse(node_data["document"])
    
    if not image_nodes:
        return "No image nodes found"
    
    result = f"Found {len(image_nodes)} image nodes:\n\n"
    for i, img in enumerate(image_nodes[:50], 1):  # Limit to first 50
        result += f"{i}. {img['id']}: {img['name']}\n"
    
    if len(image_nodes) > 50:
        result += f"\n... and {len(image_nodes) - 50} more images"
    
    return result


@mcp.tool()
async def auto_export_all_images(fileKey: str, nodeId: str, outputDir: str = "images") -> str:
    """
    Automatically detect and export ALL image nodes from a Figma design.
    
    This tool:
    1. Scans the entire design for all image nodes (via imageRef hashes)
    2. Categorizes them (hero, product, icons, etc.)
    3. Exports all images automatically
    4. Creates placeholders for missing common assets (logo, icons)
    5. Generates a manifest of what was exported
    
    Args:
        fileKey: Figma file key
        nodeId: Root node ID to analyze and export from
        outputDir: Output directory for images (default: "images")
    
    Returns:
        Summary of exported images and manifest location
    """
    logger.info(f"auto_export_all_images: {fileKey}, node: {nodeId}, output: {outputDir}")
    
    import os
    import json
    import asyncio

    # Step 1: Fetch the node tree
    logger.info("Step 1: Analyzing design for image nodes...")

    node_data = await make_figma_request(
        f"/files/{fileKey}/nodes",
        params={"ids": nodeId}
    )

    if not node_data or "nodes" not in node_data:
        return "Error: Failed to fetch node data"

    def categorize_image(name: str) -> str:
        name_lower = name.lower()
        if any(x in name_lower for x in ["icon", "badge"]):
            return "icon"
        elif any(x in name_lower for x in ["logo"]):
            return "logo"
        elif any(x in name_lower for x in ["hero", "banner", "background", "bg"]):
            return "background"
        elif any(x in name_lower for x in ["product", "bottle", "supplement", "item"]):
            return "product"
        elif any(x in name_lower for x in ["testimonial", "review", "customer", "person", "user", "face", "avatar"]):
            return "testimonial"
        else:
            return "general"

    # Collect unique imageRef hashes with metadata.
    # Using imageRef instead of node IDs avoids the "I...;..." instance-ID problem —
    # component-instance children share the same imageRef as their source component.
    image_refs: dict[str, dict] = {}  # {imageRef: {name, category, node_id}}

    def traverse(node):
        if not node:
            return
        node_name = node.get("name", "Unnamed")
        node_id = node.get("id", "")
        for fill in node.get("fills", []):
            if fill.get("type") == "IMAGE":
                ref = fill.get("imageRef")
                if ref and ref not in image_refs:
                    image_refs[ref] = {
                        "name": node_name,
                        "node_id": node_id,
                        "category": categorize_image(node_name),
                    }
        for child in node.get("children", []):
            traverse(child)

    for _, ndata in node_data.get("nodes", {}).items():
        if ndata and "document" in ndata:
            traverse(ndata["document"])

    logger.info(f"Found {len(image_refs)} unique image fills (imageRefs)")

    # Group by category for reporting
    categories: dict[str, list] = {}
    for ref, meta in image_refs.items():
        cat = meta["category"]
        categories.setdefault(cat, []).append(ref)

    # Convert to absolute path if relative
    if not os.path.isabs(outputDir):
        outputDir = os.path.abspath(outputDir)
    os.makedirs(outputDir, exist_ok=True)

    exported = []
    failed = []

    if not image_refs:
        logger.info("No image fills found in this node tree")
    else:
        # Step 2: Get download URLs for all imageRefs via /files/{key}/images
        logger.info("Step 2: Fetching image fill URLs from Figma...")
        fills_data = await make_figma_request(f"/files/{fileKey}/images")
        all_fill_urls: dict[str, str] = {}
        if fills_data:
            all_fill_urls = fills_data.get("meta", {}).get("images", {})
        logger.info(f"Received {len(all_fill_urls)} fill URLs from Figma")

        # Step 3: Download images concurrently
        logger.info("Step 3: Downloading images...")

        async def download_one(client, ref: str, meta: dict):
            url = all_fill_urls.get(ref)
            if not url:
                logger.warning(f"No URL for imageRef {ref} ({meta['name']})")
                return {"success": False, "name": meta["name"], "error": "no URL"}
            safe_name = meta["name"].replace(" ", "_").replace("/", "-")[:50]
            filename = f"{meta['category']}-{ref[:8]}-{safe_name}.png"
            filepath = os.path.join(outputDir, filename)
            try:
                response = await client.get(url, timeout=30.0)
                response.raise_for_status()
                with open(filepath, "wb") as f:
                    f.write(response.content)
                size_kb = len(response.content) / 1024
                logger.info(f"✓ {filename} ({size_kb:.1f} KB)")
                return {"success": True, "filename": filename, "size_kb": size_kb,
                        "category": meta["category"], "node_id": meta["node_id"], "name": meta["name"]}
            except Exception as e:
                logger.error(f"Failed to download {meta['name']}: {e}")
                return {"success": False, "name": meta["name"], "error": str(e)}

        async with httpx.AsyncClient(timeout=60.0) as client:
            tasks = [download_one(client, ref, meta) for ref, meta in image_refs.items()]
            results = await asyncio.gather(*tasks, return_exceptions=True)

        for result in results:
            if isinstance(result, Exception):
                failed.append(f"Exception: {result}")
            elif result.get("success"):
                exported.append(result)
            else:
                failed.append(f"{result['name']} ({result.get('error', 'unknown')})")

    # Step 3b: Export INSTANCE/FRAME nodes named "Logo" as SVG
    logger.info("Step 3b: Finding logo/icon nodes for SVG export...")

    svg_export_nodes: dict[str, dict] = {}  # {node_id: {name, category}}

    def traverse_for_svg_nodes(node, parent_name=""):
        if not node:
            return
        node_id = node.get("id", "")
        node_name = node.get("name", "Unnamed")
        node_type = node.get("type", "")

        # Export INSTANCE or FRAME nodes named "logo" as SVG (whole component)
        if node_type in ["INSTANCE", "FRAME"] and "logo" in node_name.lower():
            if node_id not in svg_export_nodes:
                svg_export_nodes[node_id] = {"name": node_name, "category": "logo"}
            # Don't recurse — we'll render the whole instance as one SVG
            return

        for child in node.get("children", []):
            traverse_for_svg_nodes(child, node_name)

    for _, ndata in node_data.get("nodes", {}).items():
        if ndata and "document" in ndata:
            traverse_for_svg_nodes(ndata["document"])

    logger.info(f"Found {len(svg_export_nodes)} SVG export candidates")

    if svg_export_nodes:
        ids_param = ",".join(svg_export_nodes.keys())
        try:
            svg_render_data = await make_figma_request(
                f"/images/{fileKey}",
                params={"ids": ids_param, "format": "svg", "scale": "1"}
            )
        except Exception as e:
            logger.error(f"SVG render request failed: {e}")
            svg_render_data = None

        if svg_render_data and "images" in svg_render_data:
            svg_urls = svg_render_data["images"]

            async def download_svg_node(client, node_id: str, meta: dict, url: str):
                if not url:
                    logger.warning(f"No SVG URL for {meta['name']} ({node_id})")
                    return None
                safe_node_id = node_id.replace(":", "-").replace(";", "-")
                safe_name = meta["name"].replace(" ", "_").replace("/", "-")[:30]
                filename = f"{meta['category']}-{safe_node_id}-{safe_name}.svg"
                filepath = os.path.join(outputDir, filename)
                try:
                    response = await client.get(url, timeout=30.0)
                    response.raise_for_status()
                    with open(filepath, "wb") as f:
                        f.write(response.content)
                    logger.info(f"✓ SVG exported: {filename}")
                    return {
                        "success": True,
                        "filename": filename,
                        "name": meta["name"],
                        "category": meta["category"],
                        "node_id": safe_node_id,  # sanitized — matches html_generator lookup
                    }
                except Exception as e:
                    logger.error(f"SVG download failed for {meta['name']}: {e}")
                    return None

            async with httpx.AsyncClient(timeout=60.0) as svg_client:
                svg_tasks = [
                    download_svg_node(svg_client, nid, nmeta, svg_urls.get(nid))
                    for nid, nmeta in svg_export_nodes.items()
                ]
                svg_results = await asyncio.gather(*svg_tasks, return_exceptions=True)

            for result in svg_results:
                if result and not isinstance(result, Exception) and result.get("success"):
                    exported.append(result)
                    cat = result.get("category", "general")
                    categories.setdefault(cat, []).append(result["node_id"])

    # Step 4: Create placeholders for missing critical assets
    logger.info("Step 4: Creating placeholders for missing assets...")
    
    def create_placeholder_svg(filepath: str, width: int = 300, height: int = 300, label: str = "Placeholder"):
        """Create a simple SVG placeholder"""
        svg_content = f'''<svg width="{width}" height="{height}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#e8f0e8;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#d0e0d0;stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="{width}" height="{height}" fill="url(#grad)"/>
  <text x="50%" y="50%" font-family="Arial" font-size="14" fill="#666" text-anchor="middle" dominant-baseline="middle">
    {label}
  </text>
</svg>'''
        try:
            with open(filepath, "w") as f:
                f.write(svg_content)
            return True
        except:
            return False
    
    placeholders_created = []
    
    # Check for and create missing icon placeholders
    icon_count = len(categories.get("icon", []))
    if icon_count < 4:
        logger.info("Creating placeholder icons...")
        icon_names = ["innovating", "century", "offerings", "testing"]
        for icon_name in icon_names:
            filepath = os.path.join(outputDir, f"icon-{icon_name}.svg")
            if not os.path.exists(filepath):
                if create_placeholder_svg(filepath, 100, 100, icon_name.title()):
                    placeholders_created.append(f"icon-{icon_name}.svg")
                    logger.info(f"✓ Created placeholder: icon-{icon_name}.svg")
    
    # Create logo placeholder if missing
    logo_count = len(categories.get("logo", []))
    if logo_count == 0:
        filepath = os.path.join(outputDir, "logo-white.svg")
        if not os.path.exists(filepath):
            if create_placeholder_svg(filepath, 150, 100, "Logo"):
                placeholders_created.append("logo-white.svg")
                logger.info("✓ Created placeholder: logo-white.svg")
    
    # Step 4: Generate manifest
    logger.info("Step 4: Generating manifest...")
    
    manifest = {
        "generated_at": str(__import__('datetime').datetime.now()),
        "fileKey": fileKey,
        "outputDir": outputDir,
        "summary": {
            "total_images_found": len(image_refs),
            "images_exported": len(exported),
            "export_failures": len(failed),
            "placeholders_created": len(placeholders_created)
        },
        "by_category": {cat: len(refs) for cat, refs in categories.items()},
        "exported_files": exported,
        "failed_exports": failed,
        "placeholders": placeholders_created
    }
    
    manifest_path = os.path.join(outputDir, "manifest.json")
    with open(manifest_path, "w") as f:
        json.dump(manifest, f, indent=2)
    
    logger.info(f"✓ Manifest saved to {manifest_path}")
    
    # Generate summary report
    report = f"""
AUTO-EXPORT SUMMARY
{'=' * 60}

Total Images Found: {len(image_refs)}
Successfully Exported: {len(exported)}
Failed Exports: {len(failed)}
Placeholders Created: {len(placeholders_created)}

BY CATEGORY:
{chr(10).join(f'  {cat}: {len(refs)} images' for cat, refs in categories.items())}

EXPORTED FILES ({len(exported)}):
{chr(10).join(f'  • {e["filename"]} ({e["size_kb"]:.1f} KB) - {e["category"]}' for e in exported[:10])}
{f'  ... and {len(exported)-10} more files' if len(exported) > 10 else ''}

PLACEHOLDERS CREATED ({len(placeholders_created)}):
{chr(10).join(f'  • {p}' for p in placeholders_created)}

MANIFEST:
  Location: {manifest_path}

OUTPUT DIRECTORY:
  {outputDir}
  Total files: {len(os.listdir(outputDir))}
"""
    
    logger.info("✓ Auto-export complete!")
    return report


@mcp.tool()
async def extract_colors(fileKey: str, nodeId: str) -> str:
    """
    Extract color palette from Figma design.
    Returns CSS custom properties for all colors found.
    
    Args:
        fileKey: Figma file key
        nodeId: Node ID to analyze
    
    Returns:
        CSS custom properties with extracted colors
    """
    logger.info(f"extract_colors: {fileKey}, node: {nodeId}")
    
    # Fetch node data
    data = await make_figma_request(
        f"/files/{fileKey}/nodes",
        params={"ids": nodeId}
    )
    
    if not data or "nodes" not in data:
        return "Error: Failed to fetch node data"
    
    # Collect all colors
    colors = set()
    
    def traverse(node):
        if not node:
            return
        
        # Extract fill colors
        for fill in node.get("fills", []):
            if fill.get("type") == "SOLID" and fill.get("visible", True):
                color = fill.get("color", {})
                r = int(color.get("r", 0) * 255)
                g = int(color.get("g", 0) * 255)
                b = int(color.get("b", 0) * 255)
                colors.add((r, g, b))
        
        # Extract stroke colors
        for stroke in node.get("strokes", []):
            if stroke.get("type") == "SOLID" and stroke.get("visible", True):
                color = stroke.get("color", {})
                r = int(color.get("r", 0) * 255)
                g = int(color.get("g", 0) * 255)
                b = int(color.get("b", 0) * 255)
                colors.add((r, g, b))
        
        # Traverse children
        if "children" in node:
            for child in node["children"]:
                traverse(child)
    
    # Start traversal
    for node_key, node_data in data.get("nodes", {}).items():
        if node_data and "document" in node_data:
            traverse(node_data["document"])
    
    if not colors:
        return "No colors found"
    
    # Generate CSS custom properties
    result = ":root {\n"
    for i, (r, g, b) in enumerate(sorted(colors), 1):
        result += f"    --color-{i}: rgb({r}, {g}, {b});\n"
    result += "}\n\n"
    result += f"/* Found {len(colors)} unique colors */\n"
    
    # List colors with hex values
    result += "\n/* Color Reference:\n"
    for i, (r, g, b) in enumerate(sorted(colors), 1):
        hex_color = f"#{r:02x}{g:02x}{b:02x}"
        result += f"   --color-{i}: {hex_color} / rgb({r}, {g}, {b})\n"
    result += "*/\n"
    
    return result


@mcp.tool()
async def generate_html_structure(fileKey: str, nodeId: str, pageName: str = "Page") -> str:
    """
    Generate semantic HTML structure from Figma node hierarchy.
    Creates HTML based on ACTUAL content from Figma, not templates.
    
    Args:
        fileKey: Figma file key
        nodeId: Node ID to convert
        pageName: Name for the page title
    
    Returns:
        HTML structure with semantic elements based on actual Figma content
    """
    logger.info(f"generate_html_structure: {fileKey}, node: {nodeId} - Deprecated, use generate_smart_html instead")
    
    # This function is deprecated - redirect to generate_smart_html
    return await generate_smart_html(fileKey, nodeId, pageName, ".", "./images")


@mcp.tool()
async def generate_css_boilerplate(fileKey: str, nodeId: str, outputDir: str = ".") -> str:
    """
    Generate CSS boilerplate with extracted colors and basic styling.
    Includes reset, design tokens, and responsive breakpoints.
    Automatically saves to styles.css in the outputDir.
    
    Args:
        fileKey: Figma file key
        nodeId: Node ID to analyze
        outputDir: Directory where styles.css will be saved (default: current directory)
    
    Returns:
        Complete CSS boilerplate with design tokens
    """
    logger.info(f"generate_css_boilerplate: {fileKey}, node: {nodeId}, outputDir: {outputDir}")
    
    # Ensure outputDir is absolute path
    if not os.path.isabs(outputDir):
        # For relative paths, resolve from current working directory
        # Note: In MCP context, this is where the server process is running
        outputDir = os.path.abspath(outputDir)
        logger.warning(f"Relative path converted to absolute: {outputDir}. Consider using absolute paths.")
    
    # First extract colors
    colors_css = await extract_colors(fileKey, nodeId)
    
    # Fetch node data for dimensions
    data = await make_figma_request(
        f"/files/{fileKey}/nodes",
        params={"ids": nodeId}
    )
    
    main_node = None
    if data and "nodes" in data:
        for node_key, node_data in data.get("nodes", {}).items():
            if node_data and "document" in node_data:
                main_node = node_data["document"]
                break
    
    width = main_node.get("absoluteBoundingBox", {}).get("width", 1680) if main_node else 1680
    height = main_node.get("absoluteBoundingBox", {}).get("height", 1080) if main_node else 1080
    
    css = f"""/* Generated from Figma Design: {fileKey}, Node: {nodeId} */
/* Design Dimensions: {width}x{height}px */

/* ===== CSS Reset ===== */
* {{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}}

/* ===== Design Tokens ===== */
{colors_css}

/* ===== Base Styles ===== */
body {{
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    line-height: 1.6;
    color: var(--color-1, #333);
    background-color: #fff;
    overflow-x: hidden;
}}

.figma-container {{
    max-width: {width}px;
    margin: 0 auto;
    position: relative;
}}

img {{
    max-width: 100%;
    height: auto;
    display: block;
}}

a {{
    text-decoration: none;
    color: inherit;
}}

button {{
    cursor: pointer;
    border: none;
    background: none;
    font-family: inherit;
}}

/* ===== Layout Helpers ===== */
.hero {{
    position: relative;
    overflow: hidden;
}}

.navigation {{
    position: relative;
}}

.text {{
    margin: 0;
    padding: 0;
}}

/* ===== Responsive Breakpoints ===== */
/* Desktop: {width}px+ (design base) */
/* Tablet: 1024px and below */
/* Mobile: 768px - 1023px */
/* Small: < 768px */

@media (max-width: 1024px) {{
    .figma-container {{
        max-width: 100%;
        padding: 0 20px;
    }}
}}

@media (max-width: 768px) {{
    .figma-container {{
        padding: 0 15px;
    }}
}}

@media (max-width: 480px) {{
    .figma-container {{
        padding: 0 10px;
    }}
}}
"""
    
    # Create output directory if it doesn't exist
    os.makedirs(outputDir, exist_ok=True)
    
    # Save CSS file
    css_path = os.path.join(outputDir, "styles.css")
    with open(css_path, 'w', encoding='utf-8') as f:
        f.write(css)
    
    logger.info(f"✅ CSS saved to: {css_path}")
    
    return f"CSS boilerplate generated and saved to: {css_path}\n\n{css}"


@mcp.tool()
async def create_image_manifest(fileKey: str, nodeId: str, exportedImagesDir: str = "./images") -> str:
    """
    Create a manifest of available images by analyzing what was actually exported.
    This helps map Figma node IDs to actual file paths.
    
    Args:
        fileKey: Figma file key
        nodeId: Root node ID to analyze
        exportedImagesDir: Directory where images were exported (default: "./images")
    
    Returns:
        JSON manifest mapping image purposes to actual file paths
    """
    logger.info(f"create_image_manifest: {fileKey}, node: {nodeId}, dir: {exportedImagesDir}")
    
    import os
    import json
    from pathlib import Path
    
    # Get list of actually exported images
    images_path = Path(exportedImagesDir)
    if not images_path.exists():
        return json.dumps({"error": f"Directory {exportedImagesDir} does not exist"})
    
    exported_files = {}
    for img_file in images_path.glob("*"):
        if img_file.is_file() and img_file.suffix.lower() in ['.png', '.jpg', '.jpeg', '.svg', '.webp']:
            # Extract semantic name from filename
            name = img_file.stem.lower()
            relative_path = f"images/{img_file.name}"
            
            # Categorize images by purpose
            if "hero" in name:
                exported_files["hero_image"] = relative_path
            elif "logo" in name:
                exported_files["logo"] = relative_path
            elif "product" in name:
                if "sleep" in name:
                    exported_files["product_1"] = relative_path
                elif "wellbeing" in name or "wellness" in name:
                    exported_files["product_2"] = relative_path
                elif "product-3" in name or "product3" in name:
                    exported_files["product_3"] = relative_path
                else:
                    # Generic product images
                    key = f"product_{name.split('-')[-1]}" if '-' in name else name
                    exported_files[key] = relative_path
            elif "media" in name:
                num = name.split('-')[-1] if '-' in name else "1"
                exported_files[f"media_{num}"] = relative_path
            elif "arrow" in name:
                if "left" in name:
                    exported_files["arrow_left"] = relative_path
                elif "right" in name:
                    exported_files["arrow_right"] = relative_path
                elif "down" in name:
                    num = name.split('-')[-1] if '-' in name else "1"
                    exported_files[f"arrow_down_{num}"] = relative_path
            elif "star" in name or "rating" in name:
                exported_files["stars_rating"] = relative_path
            elif "anniversary" in name or "years" in name:
                exported_files["anniversary_logo"] = relative_path
            else:
                # Unknown - use filename as key
                exported_files[name] = relative_path
    
    manifest = {
        "exportedImagesDir": exportedImagesDir,
        "totalImages": len(exported_files),
        "images": exported_files,
        "usage_guide": {
            "hero_image": "Main hero/banner background",
            "logo": "Site/brand logo",
            "product_1": "First product image",
            "product_2": "Second product image",
            "product_3": "Third product image",
            "media_1": "First media/content image",
            "arrow_left": "Left navigation arrow",
            "arrow_right": "Right navigation arrow",
            "stars_rating": "Rating stars icon"
        }
    }
    
    return json.dumps(manifest, indent=2)


@mcp.tool()
async def generate_smart_html(
    fileKey: str, 
    nodeId: str, 
    pageName: str = "Page",
    outputDir: str = ".",
    exportedImagesDir: str = "./images"
) -> str:
    """
    Generate HTML using ACTUAL Figma content with exported images.
    This tool analyzes the Figma design and generates HTML based on real content.
    Automatically saves to index.html and script.js in the outputDir.
    
    Args:
        fileKey: Figma file key
        nodeId: Node ID to convert
        pageName: Name for the page title
        outputDir: Directory where index.html and script.js will be saved (default: current directory)
        exportedImagesDir: Directory where images were exported
    
    Returns:
        HTML structure with actual content and correct image paths
    """
    logger.info(f"generate_smart_html: {fileKey}, node: {nodeId}, outputDir: {outputDir}")
    
    # Ensure outputDir is absolute path
    if not os.path.isabs(outputDir):
        # For relative paths, resolve from current working directory
        # Note: In MCP context, this is where the server process is running
        outputDir = os.path.abspath(outputDir)
        logger.warning(f"Relative path converted to absolute: {outputDir}. Consider using absolute paths.")
    
    import json
    
    # First, try to load the existing manifest.json which contains node IDs
    # First, try to load the existing manifest.json which contains node IDs
    # If exportedImagesDir is relative, assume it's relative to outputDir
    if not os.path.isabs(exportedImagesDir):
        images_path = os.path.join(outputDir, exportedImagesDir)
    else:
        images_path = exportedImagesDir
        
    manifest_path = os.path.join(images_path, "manifest.json")
    
    if os.path.exists(manifest_path):
        try:
            with open(manifest_path, 'r') as f:
                manifest = json.load(f)
            logger.info(f"Loaded existing manifest from {manifest_path}")
        except Exception as e:
            logger.warning(f"Failed to load existing manifest: {e}")
            # Fallback
            manifest_str = await create_image_manifest(fileKey, nodeId, images_path)
            manifest = json.loads(manifest_str)
    else:
        logger.warning(f"No manifest.json found at {manifest_path}, auto-exporting images...")
        # Auto-export images to the correct directory
        await auto_export_all_images(fileKey, nodeId, images_path)
        
        # Now try loading the manifest again
        if os.path.exists(manifest_path):
            with open(manifest_path, 'r') as f:
                manifest = json.load(f)
            logger.info(f"Loaded newly exported manifest from {manifest_path}")
        else:
            # Fallback if export failed to produce manifest
            manifest_str = await create_image_manifest(fileKey, nodeId, images_path)
            manifest = json.loads(manifest_str)
    
    # Fetch node data
    data = await make_figma_request(
        f"/files/{fileKey}/nodes",
        params={"ids": nodeId}
    )
    
    if not data or "nodes" not in data:
        return "Error: Failed to fetch node data"
    
    # Get the main node
    main_node = None
    for node_key, node_data in data.get("nodes", {}).items():
        if node_data and "document" in node_data:
            main_node = node_data["document"]
            break
    
    if not main_node:
        return "Error: No document found"
    
    # ✅ USE THE FIXED GENERATOR - generates from ACTUAL content
    html = create_smart_html_from_figma_data(main_node, manifest, pageName)
    
    # Create output directory if it doesn't exist
    os.makedirs(outputDir, exist_ok=True)
    
    # Save HTML file
    html_path = os.path.join(outputDir, "index.html")
    with open(html_path, 'w', encoding='utf-8') as f:
        f.write(html)
    
    logger.info(f"✅ HTML saved to: {html_path}")
    
    # Create a basic script.js file
    script_js = """// Generated from Figma Design - Interactive Features
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded successfully');
    
    // Add smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Newsletter form handling (if present)
    const signUpButtons = document.querySelectorAll('.text.sign-up');
    signUpButtons.forEach(button => {
        button.addEventListener('click', function() {
            const emailInput = this.parentElement.querySelector('.text.your-email');
            if (emailInput) {
                alert('Newsletter signup functionality would be implemented here');
            }
        });
    });
});
"""
    
    script_path = os.path.join(outputDir, "script.js")
    with open(script_path, 'w', encoding='utf-8') as f:
        f.write(script_js)
    
    logger.info(f"✅ JavaScript saved to: {script_path}")
    
    return f"HTML and JavaScript generated and saved to: {outputDir}\n\nFiles created:\n- {html_path}\n- {script_path}\n\n{html[:500]}..."


@mcp.tool()
async def analyze_figma_for_drupal_modules(fileKey: str, nodeId: str) -> str:
    """
    Analyze Figma design to detect which Drupal modules are needed.
    
    This tool scans the Figma design and identifies sections/components that
    correspond to specific Drupal modules (e.g., carousels, slideshows, forms,
    galleries, etc.). It returns a list of recommended Drupal modules.
    
    Args:
        fileKey: Figma file key
        nodeId: Root node ID to analyze
    
    Returns:
        JSON with detected modules and their mappings to Figma sections
    """
    logger.info(f"analyze_figma_for_drupal_modules: {fileKey}, node: {nodeId}")
    
    import json
    
    # Fetch node data
    data = await make_figma_request(
        f"/files/{fileKey}/nodes",
        params={"ids": nodeId}
    )
    
    if not data or "nodes" not in data:
        return json.dumps({"error": "Failed to fetch node data"})
    
    # Module detection patterns
    module_patterns = {
        "views": ["view", "list", "grid", "table", "listing"],
        "paragraphs": ["paragraph", "section", "component", "block"],
        "slick": ["carousel", "slider", "slideshow", "slide"],
        "easy_carousel": ["carousel", "slider", "slideshow"],
        "webform": ["form", "contact", "subscribe", "newsletter", "signup", "email input"],
        "media": ["media", "video", "gallery", "image gallery"],
        "field_group": ["fieldset", "accordion", "tabs", "tabbed"],
        "social_media_links": ["social", "facebook", "twitter", "instagram", "linkedin"],
        "layout_builder": ["layout", "section", "region"],
        "search_api": ["search", "filter", "facet"],
        "testimonials": ["testimonial", "review", "quote"],
        "features": ["feature", "highlight", "benefit"],
        "ckeditor": ["text editor", "wysiwyg", "rich text"],
        "entity_reference": ["reference", "related", "linked"],
    }
    
    detected_modules = {}
    section_mappings = []
    
    def analyze_node(node, path=""):
        if not node:
            return
        
        node_name = node.get("name", "").lower()
        node_type = node.get("type", "")
        node_id = node.get("id", "")
        
        # Check node name against module patterns
        for module, patterns in module_patterns.items():
            for pattern in patterns:
                if pattern in node_name:
                    if module not in detected_modules:
                        detected_modules[module] = {
                            "module_name": module,
                            "occurrences": 0,
                            "sections": [],
                            "confidence": "high" if pattern == node_name else "medium"
                        }
                    
                    detected_modules[module]["occurrences"] += 1
                    detected_modules[module]["sections"].append({
                        "name": node.get("name", "Unnamed"),
                        "id": node_id,
                        "type": node_type,
                        "path": path,
                        "matched_pattern": pattern
                    })
                    
                    section_mappings.append({
                        "figma_section": node.get("name", "Unnamed"),
                        "figma_id": node_id,
                        "recommended_module": module,
                        "reason": f"Matched pattern: '{pattern}'"
                    })
                    break
        
        # Recursively analyze children
        if "children" in node:
            for child in node["children"]:
                analyze_node(child, f"{path}/{node.get('name', 'Unnamed')}")
    
    # Start analysis
    for node_key, node_data in data.get("nodes", {}).items():
        if node_data and "document" in node_data:
            analyze_node(node_data["document"])
    
    # Prepare result
    result = {
        "fileKey": fileKey,
        "nodeId": nodeId,
        "total_modules_detected": len(detected_modules),
        "detected_modules": detected_modules,
        "section_mappings": section_mappings,
        "recommended_dependencies": list(detected_modules.keys()),
        "summary": {
            module: details["occurrences"]
            for module, details in detected_modules.items()
        }
    }
    
    return json.dumps(result, indent=2)


@mcp.tool()
async def generate_twig_template(
    fileKey: str,
    nodeId: str,
    themeName: str = "",
    outputDir: str = "theme",
    exportImages: bool = True,
    generateCss: bool = True,
    detectModules: bool = True
) -> str:
    """
    Generate a complete Drupal theme from Figma design.
    Creates a Drupal-friendly theme structure based on the ebanista reference theme.
    NOW WITH AUTOMATIC MODULE DETECTION!
    
    This tool:
    0. Analyzes Figma design to detect required Drupal modules (if detectModules=True)
    1. Fetches Figma node data
    2. Extracts colors and generates CSS/SCSS
    3. Exports images (if exportImages=True)
    4. Generates complete Drupal theme structure with module dependencies:
       - theme.info.yml
       - theme.libraries.yml
       - theme.theme (PHP preprocessing)
       - composer.json
       - package.json
       - gulpfile.js
       - SCSS structure (abstracts, base, components, layout)
       - Twig templates (page, node, paragraphs)
       - JavaScript files
       - README.md
    
    The output follows Drupal conventions and the ebanista theme structure with:
    - Proper file organization (css/, sass/, js/, templates/, images/)
    - Library definitions for CSS/JS assets
    - Twig templates that reference correct asset paths
    - SCSS build system with Gulp
    - Bootstrap Barrio as base theme
    
    Args:
        fileKey: Figma file key
        nodeId: Node ID to convert (the design you want to convert)
        themeName: Theme name (default: uses Figma design name)
        outputDir: Output directory for theme files (default: "theme")
        exportImages: Whether to export images (default: True)
        generateCss: Whether to generate CSS/SCSS (default: True)
        detectModules: Whether to auto-detect required Drupal modules (default: True)
    
    Returns:
        Summary of generated theme with file list and next steps
    """
    logger.info(f"generate_twig_template called: fileKey={fileKey}, nodeId={nodeId}, themeName={themeName}, detectModules={detectModules}")
    
    detected_modules = []
    
    # Step 0: Detect required Drupal modules if requested
    if detectModules:
        logger.info("Step 0: Analyzing Figma design for required Drupal modules...")
        try:
            module_analysis = await analyze_figma_for_drupal_modules(fileKey, nodeId)
            module_data = json.loads(module_analysis)
            detected_modules = module_data.get("recommended_dependencies", [])
            logger.info(f"Detected {len(detected_modules)} modules: {detected_modules}")
        except Exception as e:
            logger.warning(f"Module detection failed: {e}")
            detected_modules = []
    
    import os
    import json
    
    # Step 1: Fetch Figma node data
    logger.info("Step 1: Fetching Figma node data...")
    data = await make_figma_request(f"/files/{fileKey}/nodes", params={"ids": nodeId})
    
    if not data or "nodes" not in data:
        return "Error: Failed to fetch node data from Figma"
    
    # Get the main node
    main_node = None
    for node_key, node_data in data.get("nodes", {}).items():
        if node_data and "document" in node_data:
            main_node = node_data["document"]
            break
    
    if not main_node:
        return "Error: No document found in Figma data"
    
    # Determine theme name from Figma design if not provided
    if not themeName:
        themeName = main_node.get('name', 'figma_theme')
    
    # Clean theme name for Drupal (lowercase, underscores)
    themeName = themeName.lower().replace(' ', '_').replace('-', '_')
    
    logger.info(f"Theme name: {themeName}")
    
    # Step 2: Export images if requested
    exported_images = {}
    if exportImages:
        logger.info("Step 2: Exporting images...")
        
        # Use auto_export to get all images
        export_result = await auto_export_all_images(fileKey, nodeId, f"{outputDir}/{themeName}/images")
        logger.info(f"Image export result: {export_result[:200]}...")
        
        # Create image manifest
        try:
            manifest_str = await create_image_manifest(fileKey, nodeId, f"{outputDir}/{themeName}/images")
            manifest = json.loads(manifest_str)
            exported_images = manifest.get("images", {})
            logger.info(f"Found {len(exported_images)} exported images")
        except Exception as e:
            logger.warning(f"Failed to create image manifest: {e}")
    
    # Step 3: Generate CSS and HTML if requested
    css_content = ""
    html_content = ""
    if generateCss:
        logger.info("Step 3: Generating Smart HTML & CSS...")
        
        # Use the refactored generator to get pixel-perfect HTML/CSS
        # We need the manifest for this
        if not exported_images and exportImages:
             # Try to load manifest if we just exported images
             try:
                manifest_path = f"{outputDir}/{themeName}/images/manifest.json"
                if os.path.exists(manifest_path):
                    with open(manifest_path, 'r') as f:
                        manifest = json.load(f)
                else:
                    manifest = {"images": {}}
             except:
                manifest = {"images": {}}
        else:
            manifest = {"images": exported_images if exported_images else {}}

        # Generate the smart HTML/CSS
        # Use ../images/ prefix because CSS will be in css/style.css and images in images/
        html_content = create_smart_html_from_figma_data(main_node, manifest, themeName, image_prefix="../images/")
        
        # Extract the CSS from the HTML (it's in <style> tags)
        import re
        style_match = re.search(r'<style>(.*?)</style>', html_content, re.DOTALL)
        if style_match:
            css_content = style_match.group(1)
            # Remove the style block from HTML to avoid duplication if we're saving CSS separately
            # But for Drupal twig, we might want to keep it or move it to a library
            # For now, let's keep it in the HTML or let the generator handle it
            pass
        
        logger.info(f"Generated Smart HTML: {len(html_content)} chars")
        logger.info(f"Extracted CSS: {len(css_content)} chars")
    
    # Step 4: Initialize Drupal theme generator
    logger.info(f"Step 4: Generating Drupal theme structure for '{themeName}'...")
    
    theme_gen = DrupalThemeGenerator(themeName, outputDir)
    
    # Step 5: Generate complete theme structure
    logger.info("Step 5: Creating theme files...")
    generated_files = theme_gen.generate_theme_structure(
        figma_data=main_node,
        css_content=css_content,
        html_content=html_content,
        exported_images=exported_images,
        figma_file_key=fileKey,
        required_modules=detected_modules
    )
    
    # Step 6: Generate summary
    logger.info("Step 6: Generating summary...")
    summary = theme_gen.generate_summary(generated_files)
    
    logger.info("✓ Drupal theme generation complete!")
    
    return summary


def main():
    """Run the MCP server."""
    logger.info("Starting Figma MCP Server...")
    logger.info(f"Token configured: {bool(FIGMA_ACCESS_TOKEN)}")
    
    # Run server with stdio transport (for Claude Desktop, etc.)
    mcp.run(transport='stdio')


if __name__ == "__main__":
    main()

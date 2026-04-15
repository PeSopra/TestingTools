import math
from typing import Any, Dict, List, Optional, Tuple, Set

# Component instance detection and rendering
def is_component_instance(node: Dict[str, Any]) -> bool:
    """Check if node is a component instance."""
    return node.get("type") == "INSTANCE"

def should_skip_component(node: Dict[str, Any]) -> bool:
    """
    Determine if a component instance should be skipped entirely.
    Keep all components visible so the full design renders.
    """
    return False

def generate_component_image(node: Dict[str, Any], image_manifest: Dict[str, Any], depth: int, parent_bounds: Optional[Dict[str, float]], parent_border_width: float, image_prefix: str = "images/") -> Tuple[str, str]:
    """
    Generate HTML/CSS for a component instance rendered as an image.
    """
    node_id = node.get("id", "").replace(":", "-").replace(";", "-")
    safe_id = f"node-{node_id}"
    node_name = node.get("name", "Unnamed")
    
    # Look for exported image in manifest
    image_path = find_image_in_manifest_for_component(node_id, node_name, image_manifest, image_prefix)
    
    if not image_path:
        # No image found, skip this component
        return "", ""
    
    # Generate positioning CSS
    bounds = node.get("absoluteBoundingBox", {})
    css_rules = []
    
    if parent_bounds:
        x = bounds.get("x", 0)
        y = bounds.get("y", 0)
        rel_x = x - parent_bounds.get("x", 0) - parent_border_width
        rel_y = y - parent_bounds.get("y", 0) - parent_border_width
        
        css_rules.append("    position: absolute;")
        css_rules.append(f"    left: {rel_x}px;")
        css_rules.append(f"    top: {rel_y}px;")
    
    width = bounds.get("width", 0)
    height = bounds.get("height", 0)
    if width: css_rules.append(f"    width: {width}px;")
    if height: css_rules.append(f"    height: {height}px;")
    
    # Generate CSS
    css = f"#{safe_id} {{\n" + "\n".join(css_rules) + "\n}}\n\n"
    
    # Generate HTML
    indent = "    " * depth
    class_name = node_name.replace(" ", "-").replace("/", "-").lower()
    class_name = "".join(c for c in class_name if c.isalnum() or c in "-_")
    
    html = f'{indent}<img id="{safe_id}" class="component-image {class_name}" src="{image_path}" alt="{node_name}" />\n'
    
    return html, css

def find_image_in_manifest_for_component(node_id: str, node_name: str, manifest: Dict[str, Any], prefix: str = "images/", image_ref: str = "") -> str:
    """
    Find image path for a component instance in the manifest.
    Delegates to find_image_in_manifest with imageRef support.
    """
    return find_image_in_manifest(node_id, node_name, manifest, prefix, image_ref)

def generate_html_from_node(
    node: Dict[str, Any], 
    image_manifest: Dict[str, Any], 
    depth: int = 0, 
    parent_bounds: Optional[Dict[str, float]] = None, 
    parent_is_auto_layout: bool = False,
    parent_border_width: float = 0,
    image_prefix: str = "images/",
    fonts_used: Set[str] = None
) -> Tuple[str, str]:
    """
    Main entry point for generating HTML/CSS from a Figma node.
    Refactored for better modularity and correctness.
    """
    if fonts_used is None:
        fonts_used = set()

    if not node.get("visible", True):
        return "", ""
    
    # Skip mask nodes - they are used for clipping, not rendering
    if node.get("isMask", False):
        return "", ""
    
    # Skip complex component instances that shouldn't render in this view
    if should_skip_component(node):
        return "", ""

    node_id = node.get("id", "").replace(":", "-").replace(";", "-")
    safe_id = f"node-{node_id}"
    node_name = node.get("name", "Unnamed")
    node_type = node.get("type", "UNKNOWN")

    # Early-exit: INSTANCE nodes with a manifest SVG/image export → render as <img>, skip children.
    # This handles logo components and other exported instances (e.g. logo exported as SVG).
    if node_type == "INSTANCE":
        img_path = find_image_in_manifest(node_id, node_name, image_manifest, image_prefix)
        if img_path:
            css_rules = extract_styles(node, image_manifest, depth, parent_bounds, parent_is_auto_layout, parent_border_width, image_prefix)
            # Remove any background-color that might come from invisible fills
            css_rules = [r for r in css_rules if "background-color" not in r]
            css_rules.append("    object-fit: contain;")
            css = f"#{safe_id} {{\n" + "\n".join(css_rules) + "\n}\n\n"
            indent = "    " * depth
            class_name = node_name.replace(" ", "-").replace("/", "-").replace("'", "").replace('"', "").lower()
            class_name = "".join(c for c in class_name if c.isalnum() or c in "-_")
            html = f'{indent}<img id="{safe_id}" class="{class_name}" src="{img_path}" alt="{node_name}" />\n'
            return html, css

    # Collect font if it's a text node
    if node_type == "TEXT":
        style = node.get("style", {})
        font_family = style.get("fontFamily")
        if font_family:
            fonts_used.add(font_family)
    
    # 1. Determine Tag
    tag = determine_tag(node_type, node_name)
    
    # 2. Extract Styles
    css_rules = extract_styles(node, image_manifest, depth, parent_bounds, parent_is_auto_layout, parent_border_width, image_prefix)
    
    # 3. Generate CSS String
    css = ""
    if css_rules:
        css = f"#{safe_id} {{\n" + "\n".join(css_rules) + "\n}\n\n"
    
    # 4. Process Children
    children_html = ""
    children_css = ""
    
    children = node.get("children", [])
    is_auto_layout = node.get("layoutMode") in ["HORIZONTAL", "VERTICAL"]
    current_border_width = get_border_width(node)
    
    # Pass current node's bounds as parent_bounds for children
    current_bounds = node.get("absoluteBoundingBox", {})
    
    for child in children:
        c_html, c_css = generate_html_from_node(
            child, 
            image_manifest, 
            depth + 1, 
            current_bounds, 
            is_auto_layout,
            current_border_width,
            image_prefix,
            fonts_used
        )
        children_html += c_html
        children_css += c_css
        
    # 5. Assemble HTML
    indent = "    " * depth
    text_content = get_text_content(node)
    
    class_name = node_name.replace(" ", "-").replace("/", "-").replace("'", "").replace("\"", "").replace("“", "").lower()
    # Sanitize class name
    class_name = "".join(c for c in class_name if c.isalnum() or c in "-_")
    
    html = f'{indent}<{tag} id="{safe_id}" class="{class_name}"'
    
    if text_content:
        html += f'>{text_content}</{tag}>\n'
    elif children_html:
        html += f'>\n{children_html}{indent}</{tag}>\n'
    else:
        html += f'></{tag}>\n'
        
    return html, css + children_css

def determine_tag(node_type: str, name: str) -> str:
    name_lower = name.lower()
    if "button" in name_lower: return "button"
    if "nav" in name_lower: return "nav"
    if "header" in name_lower: return "header"
    if "footer" in name_lower: return "footer"
    if "section" in name_lower: return "section"
    if "article" in name_lower: return "article"
    if "aside" in name_lower: return "aside"
    if "h1" in name_lower: return "h1"
    if "h2" in name_lower: return "h2"
    if "h3" in name_lower: return "h3"
    
    if node_type == "TEXT":
        # Could refine based on fontSize, but p/div is safe
        return "div" 
    if node_type == "IMAGE": return "div"
    return "div"

def get_border_width(node: Dict[str, Any]) -> float:
    strokes = node.get("strokes", [])
    weight = node.get("strokeWeight", 0)
    if strokes and weight > 0 and any(s.get("visible", True) for s in strokes):
        return weight
    return 0

def extract_styles(
    node: Dict[str, Any], 
    image_manifest: Dict[str, Any],
    depth: int,
    parent_bounds: Optional[Dict[str, float]],
    parent_is_auto_layout: bool,
    parent_border_width: float,
    image_prefix: str = "images/"
) -> List[str]:
    rules = []
    
    # Positioning
    if parent_is_auto_layout:
        # Flex item — must be position:relative so it forms a containing block
        # for any absolutely-positioned children (position:static does NOT do this)
        rules.append("    position: relative;")
        
        # Flex sizing
        layout_grow = node.get("layoutGrow", 0)
        layout_align = node.get("layoutAlign", "INHERIT")
        
        if layout_grow == 1:
            rules.append("    flex-grow: 1;")
        
        if layout_align == "STRETCH":
            rules.append("    align-self: stretch;")
        elif layout_align == "CENTER":
            rules.append("    align-self: center;")
            
        # If fixed width/height in auto layout
        sizing_h = node.get("layoutSizingHorizontal", "FIXED")
        sizing_v = node.get("layoutSizingVertical", "FIXED")
        
        bounds = node.get("absoluteBoundingBox", {})
        width = bounds.get("width", 0)
        height = bounds.get("height", 0)
        
        if sizing_h == "FIXED" and width:
            rules.append(f"    width: {width}px;")
        elif sizing_h == "FILL":
            rules.append("    width: 100%;")
            
        if sizing_v == "FIXED" and height:
            rules.append(f"    height: {height}px;")
        elif sizing_v == "FILL":
            rules.append("    height: 100%;")

    elif parent_bounds:
        # Absolute positioning
        bounds = node.get("absoluteBoundingBox", {})
        x = bounds.get("x", 0)
        y = bounds.get("y", 0)
        
        # Calculate relative position accounting for parent border
        rel_x = x - parent_bounds.get("x", 0) - parent_border_width
        rel_y = y - parent_bounds.get("y", 0) - parent_border_width
        
        rules.append("    position: absolute;")
        rules.append(f"    left: {rel_x}px;")
        rules.append(f"    top: {rel_y}px;")
        
        # Dimensions for absolute items
        width = bounds.get("width", 0)
        height = bounds.get("height", 0)
        if width: rules.append(f"    width: {width}px;")
        if height: rules.append(f"    height: {height}px;")
        
        # Z-Index
        # rules.append(f"    z-index: {depth * 10};") # Removed to rely on DOM order
    
    # AutoLayout (Flexbox) Container Styles
    layout_mode = node.get("layoutMode")
    if layout_mode in ["HORIZONTAL", "VERTICAL"]:
        rules.append("    display: flex;")
        rules.append(f"    flex-direction: {'row' if layout_mode == 'HORIZONTAL' else 'column'};")
        
        gap = node.get("itemSpacing", 0)
        if gap: rules.append(f"    gap: {gap}px;")
        
        # Padding
        pt = node.get("paddingTop", 0)
        pr = node.get("paddingRight", 0)
        pb = node.get("paddingBottom", 0)
        pl = node.get("paddingLeft", 0)
        if any([pt, pr, pb, pl]):
            rules.append(f"    padding: {pt}px {pr}px {pb}px {pl}px;")
            
        # Alignment
        primary = node.get("primaryAxisAlignItems", "MIN")
        counter = node.get("counterAxisAlignItems", "MIN")
        
        justify_map = {"MIN": "flex-start", "CENTER": "center", "MAX": "flex-end", "SPACE_BETWEEN": "space-between"}
        align_map = {"MIN": "flex-start", "CENTER": "center", "MAX": "flex-end", "BASELINE": "baseline"}
        
        rules.append(f"    justify-content: {justify_map.get(primary, 'flex-start')};")
        rules.append(f"    align-items: {align_map.get(counter, 'flex-start')};")

    # Visual Styles (Fills, Strokes, Effects)
    rules.extend(get_visual_styles(node, image_manifest, image_prefix))
    
    # Typography
    if node.get("type") == "TEXT":
        rules.extend(get_text_styles(node))
        
    # Overflow
    if node.get("clipsContent"):
        rules.append("    overflow: hidden;")
    
    return rules

def get_visual_styles(node: Dict[str, Any], manifest: Dict[str, Any], image_prefix: str = "images/") -> List[str]:
    rules = []
    # Background / Fills
    # Skip background color for TEXT nodes as their fills are for text color
    if node.get("type") == "TEXT":
        return rules

    fills = node.get("fills", [])
    for fill in fills:
        if not fill.get("visible", True): continue
        if fill["type"] == "SOLID":
            c = fill["color"]
            rules.append(f"    background-color: rgba({int(c['r']*255)}, {int(c['g']*255)}, {int(c['b']*255)}, {c.get('a', 1)});")
            break # Only support one background for now
        elif fill["type"] == "IMAGE":
            # Handle Image — use imageRef hash for reliable matching
            image_ref = fill.get("imageRef", "")
            image_path = find_image_in_manifest(node.get("id", ""), node.get("name", ""), manifest, image_prefix, image_ref)
            if image_path:
                rules.append(f"    background-image: url('{image_path}');")
                rules.append(f"    background-size: cover;")
                rules.append(f"    background-position: center;")
                rules.append(f"    background-repeat: no-repeat;")
        elif fill["type"] in ["GRADIENT_LINEAR", "GRADIENT_RADIAL", "GRADIENT_ANGULAR", "GRADIENT_DIAMOND"]:
            gradient_stops = fill.get("gradientStops", [])
            if gradient_stops:
                stops_str = ", ".join(
                    f"rgba({int(s['color']['r']*255)}, {int(s['color']['g']*255)}, {int(s['color']['b']*255)}, {s['color'].get('a', 1):.3f}) {s['position']*100:.1f}%"
                    for s in gradient_stops
                )
                handles = fill.get("gradientHandlePositions", [])
                if fill["type"] == "GRADIENT_LINEAR":
                    if len(handles) >= 2:
                        dx = handles[1]["x"] - handles[0]["x"]
                        dy = handles[1]["y"] - handles[0]["y"]
                        angle_deg = (90 - math.degrees(math.atan2(-dy, dx))) % 360
                    else:
                        angle_deg = 180
                    rules.append(f"    background-image: linear-gradient({angle_deg:.1f}deg, {stops_str});")
                elif fill["type"] == "GRADIENT_RADIAL":
                    cx = handles[0]["x"] * 100 if handles else 50
                    cy = handles[0]["y"] * 100 if handles else 50
                    rules.append(f"    background-image: radial-gradient(ellipse at {cx:.1f}% {cy:.1f}%, {stops_str});")
                elif fill["type"] in ["GRADIENT_ANGULAR", "GRADIENT_DIAMOND"]:
                    rules.append(f"    background-image: conic-gradient({stops_str});")
            break  # Use first gradient fill, same as SOLID
            
    # Borders / Strokes
    strokes = node.get("strokes", [])
    weight = node.get("strokeWeight", 0)
    if strokes and weight > 0:
        for stroke in strokes:
            if not stroke.get("visible", True): continue
            if "color" in stroke:
                c = stroke["color"]
                rules.append(f"    border: {weight}px solid rgba({int(c['r']*255)}, {int(c['g']*255)}, {int(c['b']*255)}, {c.get('a', 1)});")
                break
            
    # Radius — support individual corner radii (rectangleCornerRadii) or uniform cornerRadius
    radii = node.get("rectangleCornerRadii")  # [topLeft, topRight, bottomRight, bottomLeft]
    if radii and len(radii) == 4 and any(r > 0 for r in radii):
        rules.append(f"    border-radius: {radii[0]}px {radii[1]}px {radii[2]}px {radii[3]}px;")
    else:
        radius = node.get("cornerRadius", 0)
        if radius: rules.append(f"    border-radius: {radius}px;")
    
    # Effects (Shadows)
    effects = node.get("effects", [])
    shadows = []
    for effect in effects:
        if not effect.get("visible", True): continue
        if effect["type"] == "DROP_SHADOW" and "color" in effect:
            c = effect["color"]
            o = effect["offset"]
            r = effect["radius"]
            spread = effect.get("spread", 0)
            shadows.append(f"{o['x']}px {o['y']}px {r}px {spread}px rgba({int(c['r']*255)}, {int(c['g']*255)}, {int(c['b']*255)}, {c.get('a', 1)})")
    if shadows:
        rules.append(f"    box-shadow: {', '.join(shadows)};")
        
    # Opacity
    opacity = node.get("opacity", 1)
    if opacity < 1:
        rules.append(f"    opacity: {opacity};")
        
    return rules

def get_text_styles(node: Dict[str, Any]) -> List[str]:
    rules = []
    style = node.get("style", {})
    
    # Font Family
    font_family = style.get("fontFamily", "sans-serif")
    
    # Font Fallbacks
    if font_family == "Sofia Pro":
        rules.append(f"    font-family: '{font_family}', 'Montserrat', sans-serif;")
    else:
        rules.append(f"    font-family: '{font_family}', sans-serif;")
    
    # Font Weight
    rules.append(f"    font-weight: {style.get('fontWeight', 400)};")
    
    # Font Size
    rules.append(f"    font-size: {style.get('fontSize', 16)}px;")
    
    # Line Height
    line_height_unit = style.get("lineHeightUnit", "AUTO")
    if line_height_unit == "AUTO":
        rules.append("    line-height: normal;")
    elif line_height_unit == "PIXELS":
        rules.append(f"    line-height: {style.get('lineHeightPx', 'normal')}px;")
    elif line_height_unit == "PERCENT":
        rules.append(f"    line-height: {style.get('lineHeightPercent', 100)}%;")
        
    # Letter Spacing
    letter_spacing = style.get("letterSpacing", 0)
    if letter_spacing != 0:
        rules.append(f"    letter-spacing: {letter_spacing}px;")
        
    # Text Align
    rules.append(f"    text-align: {style.get('textAlignHorizontal', 'LEFT').lower()};")
    
    # Text Decoration
    text_decoration = style.get("textDecoration", "NONE")
    if text_decoration == "STRIKETHROUGH":
        rules.append("    text-decoration: line-through;")
    elif text_decoration == "UNDERLINE":
        rules.append("    text-decoration: underline;")
        
    # Text Transform
    text_case = style.get("textCase", "ORIGINAL")
    if text_case == "UPPER":
        rules.append("    text-transform: uppercase;")
    elif text_case == "LOWER":
        rules.append("    text-transform: lowercase;")
    elif text_case == "TITLE":
        rules.append("    text-transform: capitalize;")
    
    # Vertical Alignment via Flexbox
    v_align = style.get("textAlignVertical", "TOP")
    if v_align in ["CENTER", "BOTTOM"]:
        rules.append("    display: flex;")
        rules.append("    flex-direction: column;")
        rules.append(f"    justify-content: {'center' if v_align == 'CENTER' else 'flex-end'};")
        
    # Color
    fills = node.get("fills", [])
    if fills and fills[0]["type"] == "SOLID" and "color" in fills[0]:
        c = fills[0]["color"]
        rules.append(f"    color: rgba({int(c['r']*255)}, {int(c['g']*255)}, {int(c['b']*255)}, {c.get('a', 1)});")
        
    return rules

def get_text_content(node: Dict[str, Any]) -> str:
    if node.get("type") == "TEXT":
        text = node.get("characters", "")
        # Replace single quotes with curly quotes to avoid Twig syntax errors
        return text.replace("'", "’")
    return ""

def find_image_in_manifest(node_id: str, node_name: str, manifest: Dict[str, Any], prefix: str = "images/", image_ref: str = "") -> str:
    """Find image path in manifest by imageRef hash, node ID, or name."""
    exported_files = manifest.get("exported_files", [])

    # 1. Match by imageRef hash (first 8 chars) — most reliable with new export system
    if image_ref:
        ref_prefix = image_ref[:8]
        for img in exported_files:
            if ref_prefix in img.get("filename", ""):
                filename = img.get("filename", "")
                if filename:
                    return f"{prefix}{filename}"

    # 2. Search by node ID
    for img in exported_files:
        if img.get("node_id") == node_id:
            filename = img.get("filename", "")
            if filename:
                return f"{prefix}{filename}"

    # 3. Fallback: match by name
    node_name_lower = node_name.lower().replace(" ", "").replace("-", "").replace("_", "")
    for img in exported_files:
        img_name = img.get("name", "").lower().replace(" ", "").replace("-", "").replace("_", "")
        if node_name_lower and (node_name_lower in img_name or img_name in node_name_lower):
            filename = img.get("filename", "")
            if filename:
                return f"{prefix}{filename}"

    return ""

def create_smart_html_from_figma_data(node: Dict[str, Any], image_manifest: Dict[str, Any], page_name: str = "Page", image_prefix: str = "images/") -> str:
    # Wrapper to match existing interface
    body_html = ""
    css = ""
    fonts_used = set()
    
    # Root container
    bounds = node.get("absoluteBoundingBox", {})
    width = bounds.get("width", 1440)
    height = bounds.get("height", 900)
    
    for child in node.get("children", []):
        c_html, c_css = generate_html_from_node(child, image_manifest, depth=1, parent_bounds=bounds, image_prefix=image_prefix, fonts_used=fonts_used)
        body_html += c_html
        css += c_css
        
    # Generate Google Fonts URL
    google_fonts_link = ""
    if fonts_used:
        fonts_query = []
        # Add Montserrat if Sofia Pro is used
        if "Sofia Pro" in fonts_used:
            fonts_used.add("Montserrat")
            
        for font in sorted(fonts_used):
            # Skip non-Google fonts if known
            if font in ["Sofia Pro", "Arial", "Helvetica", "Times New Roman"]:
                continue
                
            # Replace spaces with +
            font_clean = font.replace(" ", "+")
            fonts_query.append(f"family={font_clean}:wght@300;400;500;600;700")
        
        if fonts_query:
            query_str = "&".join(fonts_query)
            google_fonts_link = f'<link href="https://fonts.googleapis.com/css2?{query_str}&display=swap" rel="stylesheet">'
        
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{page_name}</title>
    {google_fonts_link}
    <style>
        * {{ box-sizing: border-box; margin: 0; padding: 0; }}
        body {{ font-family: sans-serif; }}
        #figma-root {{ position: relative; width: {width}px; min-height: {height}px; margin: 0 auto; overflow-x: hidden; background-color: #fff; }}
        {css}
    </style>
</head>
<body>
    <div id="figma-root">
        {body_html}
    </div>
</body>
</html>"""

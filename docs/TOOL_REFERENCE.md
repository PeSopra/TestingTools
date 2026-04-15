# Figma MCP Server - Tool Reference

## Available Tools (12 Total)

### 1. Core Tools

#### `whoami()`
Get authenticated Figma user information.
- Returns: email, ID, handle

#### `fetch_node_data(fileKey, nodeId)`
Fetch complete Figma node structure with all properties.
- Args: fileKey, nodeId (e.g., "1:182" or "1-182")
- Returns: Detailed JSON with hierarchy, dimensions, properties

### 2. Image Analysis & Export

#### `analyze_images(fileKey, nodeId)`
Find all image nodes in a Figma design.
- Returns: List of image nodes with IDs, names, and categories

#### `auto_export_all_images(fileKey, nodeId, outputDir="images")`
**Main image export tool** - Automatically detects and exports all images.
- Scans design for all image nodes
- Categorizes them (hero, product, icons, backgrounds, etc.)
- Exports to outputDir
- Creates placeholders for missing assets
- Generates manifest.json with results
- Returns: Summary report with counts and file list

#### `export_images(fileKey, nodeIds, outputDir, scale=2.0, format="png")`
Export specific images by node IDs.
- nodeIds: Comma-separated list (e.g., "606:10,606:12")
- scale: 1.0, 2.0, 3.0, 4.0
- format: png, jpg, svg, pdf

### 3. Design System Extraction

#### `extract_colors(fileKey, nodeId)`
Extract complete color palette from design.
- Returns: CSS custom properties with all colors
- Includes hex and RGB values
- Returns color reference guide

### 4. HTML/CSS Generation

#### `generate_html_structure(fileKey, nodeId, pageName="Page")`
Generate semantic HTML from Figma node hierarchy.
- Analyzes actual Figma content (not templates)
- Creates proper semantic structure
- Returns: Complete HTML document

#### `generate_css_boilerplate(fileKey, nodeId)`
Generate CSS with design tokens and boilerplate.
- Includes extracted colors as CSS variables
- CSS reset
- Responsive breakpoints
- Base styles

#### `create_image_manifest(fileKey, nodeId, exportedImagesDir="./images")`
Create manifest mapping image purposes to file paths.
- Analyzes exported images directory
- Categorizes images (hero, logo, products, etc.)
- Returns: JSON manifest with usage guide

#### `generate_smart_html(fileKey, nodeId, pageName="Page", exportedImagesDir="./images")`
**Complete HTML generation** - Creates HTML using actual Figma content with images.
- Fetches Figma data
- Maps exported images
- Generates complete HTML with correct image paths

### 5. Drupal Theme Generation

#### `analyze_figma_for_drupal_modules(fileKey, nodeId)`
**NEW: Analyze Figma design to detect required Drupal modules.**
- Scans all Figma nodes (frames, sections, components)
- Matches node names against module patterns
- Detects: carousels (easy_carousel/slick), forms (webform), galleries (media), etc.
- Returns: JSON with detected modules, confidence levels, and section mappings
- Example patterns:
  - "carousel", "slider" → easy_carousel
  - "form", "newsletter", "subscribe" → webform
  - "social", "facebook", "twitter" → social_media_links
  - "testimonial", "review" → testimonials
  - See [DRUPAL_MODULE_DETECTION.md](./DRUPAL_MODULE_DETECTION.md) for complete list

#### `generate_twig_template(fileKey, nodeId, themeName="", outputDir="theme", exportImages=True, generateCss=True, detectModules=True)`
**Generate complete Drupal theme** based on ebanista structure.
- **NEW**: Automatically detects and adds required Drupal modules to theme.info.yml
- Creates full theme directory structure:
  - theme.info.yml (configuration **with auto-detected module dependencies**)
  - theme.libraries.yml (asset libraries)
  - theme.theme (PHP preprocessing)
  - composer.json, package.json
  - gulpfile.js (SASS build)
  - Complete SCSS structure (abstracts, base, components, layout, pages)
  - Twig templates (block, content, field, paragraph, views)
  - JavaScript files
  - README.md
- Optionally exports images
- Optionally generates CSS/SCSS
- Optionally detects required modules (default: True)
- Returns: Summary with file list and next steps

## Common Workflows

### Workflow 1: Analyze Figma for Drupal Modules

```python
# Analyze what modules are needed before generating theme
analysis = analyze_figma_for_drupal_modules(
    fileKey="ABC123",
    nodeId="1:182"
)

# Returns JSON with:
# - detected_modules: list of modules with occurrences
# - section_mappings: which Figma sections map to which modules
# - recommended_dependencies: final list to add to theme
```

### Workflow 2: Generate Static HTML Site

```python
# 1. Extract colors
colors = extract_colors(fileKey="ABC123", nodeId="1:182")

# 2. Auto-export images
images = auto_export_all_images(fileKey="ABC123", nodeId="1:182", outputDir="output/images")

# 3. Generate complete HTML
html = generate_smart_html(fileKey="ABC123", nodeId="1:182", pageName="My Site", exportedImagesDir="output/images")

# 4. Generate CSS
css = generate_css_boilerplate(fileKey="ABC123", nodeId="1:182")
```

### Workflow 3: Generate Drupal Theme (with auto module detection)

```python
# One command does it all - including module detection!
theme = generate_twig_template(
    fileKey="ABC123",
    nodeId="1:182",
    themeName="my_custom_theme",
    outputDir="themes/custom",
    exportImages=True,
    generateCss=True,
    detectModules=True  # Default: auto-detect required modules
)

# Result: Complete Drupal theme in themes/custom/my_custom_theme/
# - theme.info.yml will include detected module dependencies
# - Console output shows which modules were detected
```

### Workflow 4: Export Specific Images

```python
# Find all images first
analysis = analyze_images(fileKey="ABC123", nodeId="1:182")

# Export specific ones
export_images(
    fileKey="ABC123",
    nodeIds="606:10,606:12,606:15",
    outputDir="my_images",
    scale=2.0,
    format="png"
)
```

## Understanding Image Export Results

The `auto_export_all_images` tool will report:
- **Total Images Found**: All nodes with IMAGE fills
- **Successfully Exported**: Nodes that Figma API could render as images
- **Failed Exports**: Nodes that returned null URLs (usually containers, not actual images)
- **Placeholders Created**: SVG placeholders for common missing assets

**Check manifest.json** for complete breakdown by category and export status.

## Tips

1. **Always start with `fetch_node_data`** to understand the design structure
2. **Use `auto_export_all_images`** for complete image export - it handles filtering and creates manifest
3. **For Drupal themes**, use `generate_twig_template` - it creates the complete structure with auto-detected modules
4. **Use semantic naming in Figma** - name layers "Product Carousel", "Newsletter Form", etc. for better module detection
5. **Analyze modules first** with `analyze_figma_for_drupal_modules` if you want to review before generating
6. **For static sites**, use `generate_smart_html` + `generate_css_boilerplate`
7. **Check logs** in `figma_mcp.log` for detailed debugging info

## Module Detection Examples

If your Figma design has:
- Frame named "Product Carousel" → Detects `easy_carousel`
- Frame named "Newsletter Subscribe" → Detects `webform`
- Frame named "Customer Testimonials" → Detects `testimonials`
- Frame named "Social Links" → Detects `social_media_links`
- Frame named "Image Gallery" → Detects `media`

These modules will be automatically added to your theme's `.info.yml` as dependencies!

For complete documentation on module detection, see [DRUPAL_MODULE_DETECTION.md](./DRUPAL_MODULE_DETECTION.md)

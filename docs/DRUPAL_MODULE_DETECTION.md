# Drupal Module Detection from Figma Designs

## Overview

The MCP server now includes **automatic Drupal module detection** based on Figma design analysis. When generating a Drupal theme, the system analyzes the Figma design structure and automatically determines which Drupal modules are needed, then adds them as dependencies in the theme's `.info.yml` file.

## How It Works

### 1. Pattern Matching

The system scans all Figma node names (frames, sections, components) and matches them against predefined patterns that correspond to specific Drupal modules.

### 2. Module Mapping

| Drupal Module | Figma Patterns Detected |
|--------------|-------------------------|
| **views** | "view", "list", "grid", "table", "listing" |
| **paragraphs** | "paragraph", "section", "component", "block" |
| **slick** | "carousel", "slider", "slideshow", "slide" |
| **easy_carousel** | "carousel", "slider", "slideshow" |
| **webform** | "form", "contact", "subscribe", "newsletter", "signup", "email input" |
| **media** | "media", "video", "gallery", "image gallery" |
| **field_group** | "fieldset", "accordion", "tabs", "tabbed" |
| **social_media_links** | "social", "facebook", "twitter", "instagram", "linkedin" |
| **layout_builder** | "layout", "section", "region" |
| **search_api** | "search", "filter", "facet" |
| **testimonials** | "testimonial", "review", "quote" |
| **features** | "feature", "highlight", "benefit" |

### 3. Automatic Integration

When you run `generate_twig_template` with `detectModules=True` (default), the system will:

1. **Scan** the entire Figma design hierarchy
2. **Detect** which sections match known module patterns
3. **Generate** a list of required modules
4. **Add** them to the theme's `.info.yml` file as dependencies

## Usage

### Using the Standalone Analysis Tool

```python
# Analyze a Figma design for required modules
result = await analyze_figma_for_drupal_modules(
    fileKey="vOCOd0Kqmf9tN4FZ6yKcz9",
    nodeId="1:182"
)
```

**Result format:**
```json
{
  "fileKey": "vOCOd0Kqmf9tN4FZ6yKcz9",
  "nodeId": "1:182",
  "total_modules_detected": 3,
  "detected_modules": {
    "easy_carousel": {
      "module_name": "easy_carousel",
      "occurrences": 2,
      "sections": [
        {
          "name": "Product Carousel",
          "id": "606:10",
          "type": "FRAME",
          "path": "/Homepage/Hero Section",
          "matched_pattern": "carousel"
        }
      ],
      "confidence": "high"
    },
    "webform": {
      "module_name": "webform",
      "occurrences": 1,
      "sections": [
        {
          "name": "Newsletter Subscribe",
          "id": "606:45",
          "type": "FRAME",
          "path": "/Homepage/Footer",
          "matched_pattern": "newsletter"
        }
      ],
      "confidence": "high"
    }
  },
  "recommended_dependencies": ["easy_carousel", "webform", "social_media_links"],
  "summary": {
    "easy_carousel": 2,
    "webform": 1,
    "social_media_links": 1
  }
}
```

### Integrated Theme Generation

```python
# Generate theme with automatic module detection
result = await generate_twig_template(
    fileKey="vOCOd0Kqmf9tN4FZ6yKcz9",
    nodeId="1:182",
    themeName="my_custom_theme",
    outputDir="/path/to/themes",
    detectModules=True  # Default is True
)
```

This will automatically add detected modules to `my_custom_theme.info.yml`:

```yaml
name: My Custom Theme
type: theme
description: 'Generated from Figma design using MCP server'
core_version_requirement: ^9 || ^10 || ^11
base theme: bootstrap_barrio
version: 1.0.0
package: Custom

dependencies:
  - node
  - block
  - field
  - easy_carousel
  - webform
  - social_media_links
  - paragraphs

libraries:
  - my_custom_theme/global-styling
  - my_custom_theme/global-script
```

## Best Practices

### 1. Name Your Figma Layers Semantically

The detection works by analyzing layer/frame names, so use descriptive names:

✅ **Good naming:**
- "Product Carousel"
- "Newsletter Form"
- "Testimonials Slider"
- "Social Media Links"
- "Contact Form"
- "Video Gallery"

❌ **Bad naming:**
- "Frame 1"
- "Group 45"
- "Rectangle 233"
- "Component Instance"

### 2. Organize Your Figma Design

Group related components together:

```
Homepage
├── Hero Section
│   └── Image Carousel
├── Features Section
│   ├── Feature 1
│   ├── Feature 2
│   └── Feature 3
├── Testimonials
│   └── Testimonials Slider
└── Footer
    ├── Newsletter Subscribe Form
    └── Social Media Links
```

### 3. Review Detected Modules

Before deploying, always review the detected modules:

1. Run `analyze_figma_for_drupal_modules` first
2. Review the JSON output
3. Verify the modules make sense for your design
4. Manually adjust if needed

### 4. Install Detected Modules

After theme generation, install the required modules:

```bash
# Using DDEV
ddev composer require 'drupal/easy_carousel:^1.2'
ddev composer require 'drupal/webform:^6.0'
ddev composer require 'drupal/social_media_links:^2.9'

# Enable modules
ddev drush en easy_carousel webform social_media_links -y
```

Or use the generated install script:

```bash
cd /path/to/your/theme
./install.sh
```

## Example Workflow

### Complete Example: E-commerce Landing Page

**Figma Design Structure:**
```
E-commerce Landing Page
├── Header
│   ├── Logo
│   └── Navigation Menu
├── Hero Carousel          ← Detected: easy_carousel
├── Product Grid           ← Detected: views
├── Testimonials Slider    ← Detected: easy_carousel, testimonials
├── Newsletter Section
│   └── Subscribe Form     ← Detected: webform
└── Footer
    ├── Social Links       ← Detected: social_media_links
    └── Contact Form       ← Detected: webform
```

**Step 1: Analyze the design**

```python
analysis = await analyze_figma_for_drupal_modules(
    fileKey="abc123xyz",
    nodeId="1:1"
)
```

**Step 2: Generate the theme**

```python
theme = await generate_twig_template(
    fileKey="abc123xyz",
    nodeId="1:1",
    themeName="ecommerce_landing",
    detectModules=True
)
```

**Step 3: Install modules**

```bash
ddev composer require 'drupal/easy_carousel:^1.2'
ddev composer require 'drupal/views:^3.0'
ddev composer require 'drupal/webform:^6.0'
ddev composer require 'drupal/social_media_links:^2.9'

ddev drush en easy_carousel views webform social_media_links -y
```

**Step 4: Enable your theme**

```bash
ddev drush theme:enable ecommerce_landing
ddev drush config:set system.theme default ecommerce_landing -y
```

## Advanced Configuration

### Disabling Module Detection

If you don't want automatic module detection:

```python
theme = await generate_twig_template(
    fileKey="abc123xyz",
    nodeId="1:1",
    themeName="my_theme",
    detectModules=False  # Disable auto-detection
)
```

### Custom Module Patterns

To add custom module patterns, edit `server.py`:

```python
module_patterns = {
    # ... existing patterns ...
    "my_custom_module": ["custom", "special", "unique"],
}
```

## Troubleshooting

### No Modules Detected

**Problem:** The analysis returns no detected modules.

**Solution:**
- Check your Figma layer names - are they descriptive?
- Use semantic naming (e.g., "carousel" instead of "frame-1")
- Review the pattern list above and align your naming

### Wrong Modules Detected

**Problem:** The system detects modules you don't need.

**Solution:**
- Rename ambiguous Figma layers
- Run analysis first, then manually edit the `.info.yml` file
- Disable auto-detection and add dependencies manually

### Missing Required Modules

**Problem:** A module you need wasn't detected.

**Solution:**
- Add it manually to the `.info.yml` file after generation
- Or add the pattern to `module_patterns` in `server.py`

## Contributing

To add support for new Drupal modules:

1. Identify the module name
2. Add patterns that would appear in Figma designs
3. Update the `module_patterns` dictionary in `server.py`
4. Test with real Figma designs
5. Document the patterns in this guide

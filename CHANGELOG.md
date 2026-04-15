# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2025-12-18

### Added
- **Automatic Drupal Module Detection** - New `analyze_figma_for_drupal_modules` MCP tool that analyzes Figma designs and automatically detects required Drupal modules based on layer names and design patterns.
- Module detection for 14+ Drupal modules including: `easy_carousel`, `slick`, `webform`, `paragraphs`, `social_media_links`, `views`, `testimonials`, `media`, `layout_builder`, `search_api`, and more.
- Auto-detection integration in `generate_twig_template` with new `detectModules` parameter (default: True).
- Automatic module dependency declarations in generated `theme.info.yml` files.
- Comprehensive documentation:
- `ddev_usage.txt` - DDEV command reference for Drupal development.

### Changed
- Enhanced `DrupalThemeGenerator` class to accept and use `required_modules` parameter.
- Updated `_generate_info_yml()` to include auto-detected module dependencies in YAML output.
- Enhanced theme generation to include essential modules (node, block, field) plus detected contrib modules.
- Updated `README.md` to highlight automatic module detection as a key feature.

### Fixed
- Indentation error in `drupal_theme_generator.py` that caused server initialization failure.
- Duplicate content in `_generate_info_yml()` template string.

### Technical Details
- Module detection uses pattern matching against Figma layer names:
  - "Product Carousel" → `easy_carousel`
  - "Newsletter Form" → `webform`
  - "Social Links" → `social_media_links`
  - "Testimonials" → `testimonials`
  - etc.
- Detection provides confidence scoring (high/medium) based on exact vs. partial pattern matches.
- Generated themes now include proper dependency declarations following Drupal standards.

## [1.0.6] - 2025-11-25

### Added
- `CHANGELOG.md` to track project history.

### Changed
- Updated `drupal_theme_generator.py` to include `bootstrap_barrio` dependency in `composer.json`.
- Refactored `html_generator_refactored.py` to sanitize text content (replacing single quotes with curly quotes) to prevent Twig syntax errors.
- Updated `server.py` to pass correct relative image paths (`../images/`) to the HTML generator.
- Updated `drupal_theme_generator.py` to embed high-fidelity HTML content into `page.html.twig`.

### Removed
- Obsolete backup files (`server copy.py-`, `server_ori_working.py-`).
- Temporary debug scripts and reproduction files.
- Old output directories (`output1` - `output7`).
- Remaining test scripts (`test_sass_fixes.py`).

## [1.0.5] - 2025-11-25

### Changed
- Initial implementation of `drupal_theme_generator.py` with SASS compilation fixes.
- Refactored HTML generation logic.

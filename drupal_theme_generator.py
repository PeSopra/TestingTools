#!/usr/bin/env python3
"""
Drupal Theme Generator for Figma MCP Server
Generates complete Drupal theme structure based on ebanista reference theme
"""

import os
import json
from pathlib import Path
from typing import Dict, Any, Optional


class DrupalThemeGenerator:
    """
    Generates a complete Drupal theme from Figma design.
    Based on ebanista theme structure with proper file organization.
    """
    
    def __init__(self, theme_name: str, output_dir: str = "theme"):
        """
        Initialize the theme generator.
        
        Args:
            theme_name: Machine name for the theme (e.g., 'my_theme')
            output_dir: Base output directory for theme files
        """
        self.theme_name = theme_name.lower().replace(' ', '_').replace('-', '_')
        self.theme_label = theme_name.replace('_', ' ').title()
        self.output_dir = Path(output_dir) / self.theme_name
        self.required_modules = []  # Will be set during generation
        
    def generate_theme_structure(self, 
                                figma_data: Dict[str, Any],
                                css_content: str = "",
                                html_content: str = "",
                                exported_images: Optional[Dict[str, str]] = None,
                                figma_file_key: str = "",
                                required_modules: Optional[list] = None) -> Dict[str, str]:
        """
        Generate complete Drupal theme structure.
        
        Args:
            figma_data: Figma node data
            css_content: Generated CSS content
            html_content: Generated HTML content
            exported_images: Dictionary mapping image purposes to file paths
            figma_file_key: The Figma file key for linking back to the design
            required_modules: List of required Drupal modules detected from Figma
            
        Returns:
            Dictionary with generated file paths and summary
        """
        # Store required modules for use in info.yml generation
        self.required_modules = required_modules or []
        
        generated_files = {}
        
        # Create directory structure
        self._create_directory_structure()
        
        # Generate theme configuration files
        generated_files['info.yml'] = self._generate_info_yml()
        generated_files['libraries.yml'] = self._generate_libraries_yml()
        generated_files['theme'] = self._generate_theme_file()
        
        # Generate composer.json
        generated_files['composer.json'] = self._generate_composer_json()
        
        # Generate package.json
        generated_files['package.json'] = self._generate_package_json()
        
        # Generate gulpfile
        generated_files['gulpfile.js'] = self._generate_gulpfile()
        
        # Generate README
        generated_files['README.md'] = self._generate_readme(figma_file_key)
        
        # Generate installation scripts
        generated_files['install.sh'] = self._generate_install_script()
        generated_files['INSTALL.md'] = self._generate_install_guide()
        
        # Generate CSS structure
        if css_content:
            generated_files['css/style.css'] = self._save_css(css_content)
            generated_files['sass/style.scss'] = self._generate_main_scss(css_content)
            generated_files['css/fonts.css'] = self._generate_fonts_css()
        
        # Generate SCSS abstracts (variables, mixins, etc.)
        generated_files.update(self._generate_scss_abstracts())
        
        # Generate base twig templates
        generated_files.update(self._generate_twig_templates(figma_data, exported_images, html_content))
        
        # Generate JS files
        generated_files['js/global.js'] = self._generate_global_js()
        
        # Generate setup script
        generated_files['setup.sh'] = self._generate_setup_script()
        
        # Copy/organize images if provided
        if exported_images:
            generated_files.update(self._organize_images(exported_images))
        
        return generated_files
    
    def _create_directory_structure(self):
        """Create all necessary directories for Drupal theme."""
        directories = [
            self.output_dir,
            self.output_dir / 'config' / 'install',
            self.output_dir / 'config' / 'schema',
            self.output_dir / 'css',
            self.output_dir / 'sass' / 'abstracts',
            self.output_dir / 'sass' / 'base',
            self.output_dir / 'sass' / 'components',
            self.output_dir / 'sass' / 'layout',
            self.output_dir / 'sass' / 'pages',
            self.output_dir / 'js',
            self.output_dir / 'images' / 'icon',
            self.output_dir / 'templates' / 'layout',
            self.output_dir / 'templates' / 'content',
            self.output_dir / 'templates' / 'paragraph',
            self.output_dir / 'templates' / 'field',
            self.output_dir / 'templates' / 'block',
            self.output_dir / 'templates' / 'navigation',
            self.output_dir / 'templates' / 'views',
        ]
        
        for directory in directories:
            directory.mkdir(parents=True, exist_ok=True)
    
    def _generate_info_yml(self) -> str:
        """Generate theme.info.yml file with detected module dependencies."""
        
        # Build dependencies section
        dependencies_section = ""
        if self.required_modules:
            dependencies_section = "\ndependencies:\n"
            # Always include essential modules first
            essential_modules = ['node', 'block', 'field']
            for module in essential_modules:
                dependencies_section += f"  - {module}\n"
            
            # Add detected modules (skip duplicates)
            for module in self.required_modules:
                if module not in essential_modules:
                    dependencies_section += f"  - {module}\n"
        
        content = f"""name: {self.theme_label}
type: theme
description: 'Generated from Figma design using MCP server'
core_version_requirement: ^9 || ^10 || ^11
base theme: bootstrap_barrio
version: 1.0.0
package: Custom
{dependencies_section}
libraries:
  - {self.theme_name}/global-styling
  - {self.theme_name}/global-script

regions:
  top_header: 'Top header'
  top_header_form: 'Top header form'
  header: Header
  header_form: 'Header form'
  primary_menu: 'Primary menu'
  secondary_menu: 'Secondary menu'
  page_top: 'Page top'
  page_bottom: 'Page bottom'
  highlighted: Highlighted
  featured_top: 'Featured top'
  breadcrumb: Breadcrumb
  content: Content
  sidebar_first: 'Sidebar first'
  sidebar_second: 'Sidebar second'
  featured_bottom_first: 'Featured bottom first'
  featured_bottom_second: 'Featured bottom second'
  featured_bottom_third: 'Featured bottom third'
  footer_first: 'Footer first'
  footer_second: 'Footer second'
  footer_third: 'Footer third'
  footer_fourth: 'Footer fourth'
  footer_fifth: 'Footer fifth'
"""
        
        filepath = self.output_dir / f"{self.theme_name}.info.yml"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _generate_libraries_yml(self) -> str:
        """Generate theme.libraries.yml file."""
        content = f"""global-styling:
  version: VERSION
  css:
    base:
      css/fonts.css: {{}}
    theme:
      css/style.css: {{}}

global-script:
  version: VERSION
  js:
    js/global.js: {{}}
  dependencies:
    - core/jquery
    - core/once
    - core/drupal
    - core/drupalSettings
"""
        
        filepath = self.output_dir / f"{self.theme_name}.libraries.yml"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _generate_theme_file(self) -> str:
        """Generate theme.theme file with preprocessing hooks."""
        content = f"""<?php

/**
 * @file
 * Functions to support theming in the {self.theme_label} theme.
 */

use Drupal\\Core\\Form\\FormStateInterface;

/**
 * Implements hook_preprocess_HOOK() for page templates.
 */
function {self.theme_name}_preprocess_page(&$variables) {{
  // Add custom variables or preprocessing here
}}

/**
 * Implements hook_preprocess_HOOK() for node templates.
 */
function {self.theme_name}_preprocess_node(&$variables) {{
  // Add custom node preprocessing here
}}

/**
 * Implements hook_preprocess_HOOK() for paragraph templates.
 */
function {self.theme_name}_preprocess_paragraph(&$variables) {{
  // Add custom paragraph preprocessing here
}}

/**
 * Implements hook_form_alter().
 */
function {self.theme_name}_form_alter(&$form, FormStateInterface $form_state, $form_id) {{
  // Form alterations here
}}
"""
        
        filepath = self.output_dir / f"{self.theme_name}.theme"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _generate_composer_json(self) -> str:
        """Generate composer.json file with Bootstrap Barrio dependency."""
        content = {
            "name": f"custom/{self.theme_name}",
            "description": f"Drupal theme {self.theme_label} generated from Figma design",
            "type": "drupal-theme",
            "license": "GPL-2.0-or-later",
            "authors": [
                {
                    "name": "Generated via Figma MCP",
                }
            ],
            "keywords": ["Theme", "Drupal", "Figma", "Bootstrap"],
            "require": {
                "drupal/core": "^10 || ^11",
                "drupal/bootstrap_barrio": "^5.5"
            },
            "extra": {
                "drush": {
                    "services": {
                        "drush.services.yml": "^10 || ^11"
                    }
                }
            }
        }
        
        filepath = self.output_dir / "composer.json"
        with open(filepath, 'w') as f:
            json.dump(content, f, indent=2)
        
        return str(filepath)
    
    def _generate_package_json(self) -> str:
        """Generate package.json for build tools."""
        content = {
            "name": self.theme_name,
            "version": "1.0.0",
            "description": f"Front-end build tools for {self.theme_label} theme.",
            "devDependencies": {
                "gulp": "^4.0.2",
                "gulp-sass": "^5.1.0",
                "gulp-autoprefixer": "^8.0.0",
                "gulp-sourcemaps": "^3.0.0",
                "gulp-cssmin": "^0.2.0",
                "gulp-plumber": "^1.2.1",
                "gulp-rename": "^2.0.0",
                "sass": "^1.75.0"
            },
            "engines": {
                "node": ">=16.x"
            },
            "private": True,
            "//": "The postinstall script is needed to work-around this Drupal core bug: https://www.drupal.org/node/2329453",
            "scripts": {
                "postinstall": "find node_modules/ -name '*.info' -type f -delete",
                "install-tools": "npm install",
                "build": "gulp",
                "compile": "gulp compile",
                "watch": "gulp watch"
            }
        }
        
        filepath = self.output_dir / "package.json"
        with open(filepath, 'w') as f:
            json.dump(content, f, indent=2)
        
        return str(filepath)
    
    def _generate_gulpfile(self) -> str:
        """Generate gulpfile.js for SASS compilation."""
        content = """const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const sourcemaps = require('gulp-sourcemaps');
const cssmin = require('gulp-cssmin');
const plumber = require('gulp-plumber');
const rename = require('gulp-rename');

// Note: This gulpfile uses the legacy Sass API.
// Consider updating to Sass's modern JavaScript API in future versions.
// See: https://sass-lang.com/documentation/js-api/

// Paths
const paths = {
  sass: {
    src: 'sass/**/*.scss',
    dest: 'css/'
  }
};

// Compile SASS
function compileSass() {
  return gulp.src('sass/style.scss')
    .pipe(plumber())
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.sass.dest));
}

// Watch files
function watchFiles() {
  gulp.watch(paths.sass.src, compileSass);
}

// Export tasks
exports.compile = compileSass;
exports.watch = watchFiles;
exports.default = gulp.series(compileSass);
"""
        
        filepath = self.output_dir / "gulpfile.js"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _generate_setup_script(self) -> str:
        """Generate setup.sh script to install dependencies."""
        content = f"""#!/bin/bash
# Setup script for {self.theme_label} theme

echo "🚀 Setting up {self.theme_label} theme..."

# 1. Check for Bootstrap Barrio base theme
echo "Checking for Bootstrap Barrio base theme..."
BARRIO_FOUND=false

# Check common locations
if [ -d "../../contrib/bootstrap_barrio" ] || [ -d "../contrib/bootstrap_barrio" ] || [ -d "../../bootstrap_barrio" ]; then
    BARRIO_FOUND=true
    echo "✅ Bootstrap Barrio found."
else
    # Check if it's installed via composer in the project
    if command -v composer &> /dev/null; then
        # Try to find project root (assuming themes/custom/theme_name structure)
        if [ -f "../../../composer.json" ]; then
            if grep -q "drupal/bootstrap_barrio" "../../../composer.json"; then
                BARRIO_FOUND=true
                echo "✅ Bootstrap Barrio listed in project composer.json."
            fi
        fi
    fi
fi

if [ "$BARRIO_FOUND" = false ]; then
    echo "⚠️ Bootstrap Barrio base theme not found."
    echo "Attempting to install via Composer..."
    
    if command -v composer &> /dev/null && [ -f "../../../composer.json" ]; then
        cd ../../..
        composer require drupal/bootstrap_barrio:^5.5
        cd - > /dev/null
        echo "✅ Bootstrap Barrio installed."
    else
        echo "❌ Could not automatically install Bootstrap Barrio."
        echo "Please run this command in your Drupal root:"
        echo "  composer require drupal/bootstrap_barrio:^5.5"
    fi
fi

# 2. Install Node dependencies for theme building
echo "Installing theme build tools..."
if command -v npm &> /dev/null; then
    npm install
    echo "✅ Build tools installed."
    
    # 3. Compile SASS
    echo "Compiling SASS..."
    npm run compile
    echo "✅ SASS compiled."
else
    echo "❌ npm not found. Please install Node.js to build the theme."
fi

echo "🎉 Setup complete! Enable the theme in Drupal admin."
"""
        
        filepath = self.output_dir / "setup.sh"
        with open(filepath, 'w') as f:
            f.write(content)
        
        # Make executable
        os.chmod(filepath, 0o755)
        
        return str(filepath)
    
    def _generate_readme(self, figma_file_key: str = "") -> str:
        """Generate README.md file."""
        
        figma_link_section = ""
        if figma_file_key:
            figma_link_section = f"\nExported from this Figma design: [View in Figma](https://www.figma.com/file/{figma_file_key})\n"
            
        content = f"""# {self.theme_label}

Drupal theme generated from Figma design using MCP server.
{figma_link_section}
## Quick Start

1. Copy this folder to `themes/custom/` in your Drupal installation.
2. Run the setup script to install dependencies:

```sh
cd themes/custom/{self.theme_name}
./setup.sh
```

This script will:
- Check for/install Bootstrap Barrio base theme
- Install Node.js dependencies
- Compile SASS files

## Manual Installation

If you prefer to install manually:

### 1. Install Base Theme
This theme requires Bootstrap Barrio.

```sh
composer require drupal/bootstrap_barrio:^5.5
```

### 2. Install Build Tools
```sh
npm install
```

### 3. Compile CSS
```sh
npm run compile
```

## Usage

Enable the theme in Drupal:
1. Go to Appearance
2. Find "{self.theme_label}"
3. Click "Install and set as default"

## Credits

This theme has been created with the Figma2Drupal MCP server, developed by Eduardo Arana - Emerging Tech Team/IT Innovation - Nestlé - 2025
"""
        
        filepath = self.output_dir / "README.md"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)


    def _generate_install_script(self) -> str:
        """Generate automated installation script for Unix/Linux/Mac."""
        content = f"""#!/bin/bash
# Installation script for {self.theme_label}
# This script automates the installation of Bootstrap Barrio and this theme

set -e  # Exit on error

echo "========================================"
echo "Installing {self.theme_label}"
echo "========================================"
echo ""

# Colors for output
RED='\\033[0;31m'
GREEN='\\033[0;32m'
YELLOW='\\033[1;33m'
NC='\\033[0m' # No Color

# Check if we're in a Drupal installation
if [ ! -f "web/index.php" ] && [ ! -f "index.php" ]; then
    echo "${{RED}}Error: This doesn't appear to be a Drupal installation.${{NC}}"
    echo "Please run this script from your Drupal root directory."
    exit 1
fi

# Determine Drupal root
if [ -f "web/index.php" ]; then
    DRUPAL_ROOT="web"
else
    DRUPAL_ROOT="."
fi

echo "${{GREEN}}Step 1: Installing Bootstrap Barrio base theme...${{NC}}"
if command -v composer &> /dev/null; then
    composer require drupal/bootstrap_barrio:^5.5
    echo "${{GREEN}}✓ Bootstrap Barrio installed via Composer${{NC}}"
else
    echo "${{YELLOW}}⚠ Composer not found. Please install Bootstrap Barrio manually.${{NC}}"
    echo "Visit: https://www.drupal.org/project/bootstrap_barrio"
fi

echo ""
echo "${{GREEN}}Step 2: Installing Node.js dependencies...${{NC}}"
if command -v npm &> /dev/null; then
    npm install
    echo "${{GREEN}}✓ Node.js dependencies installed${{NC}}"
else
    echo "${{RED}}Error: npm not found. Please install Node.js first.${{NC}}"
    echo "Visit: https://nodejs.org/"
    exit 1
fi

echo ""
echo "${{GREEN}}Step 3: Compiling SASS...${{NC}}"
npm run compile
echo "${{GREEN}}✓ SASS compiled successfully${{NC}}"

echo ""
echo "${{GREEN}}Step 4: Enabling theme via Drush (if available)...${{NC}}"
if command -v drush &> /dev/null; then
    drush theme:enable bootstrap_barrio -y
    drush theme:enable {self.theme_name} -y
    drush config-set system.theme default {self.theme_name} -y
    drush cr
    echo "${{GREEN}}✓ Theme enabled and cache cleared${{NC}}"
else
    echo "${{YELLOW}}⚠ Drush not found. Please enable the theme manually:${{NC}}"
    echo "  1. Go to /admin/appearance"
    echo "  2. Enable Bootstrap Barrio"
    echo "  3. Enable and set {self.theme_label} as default"
    echo "  4. Clear cache"
fi

echo ""
echo "========================================"
echo "${{GREEN}}Installation complete!${{NC}}"
echo "========================================"
echo ""
echo "Next steps:"
echo "  - Visit your site to see the new theme"
echo "  - Customize in ${{DRUPAL_ROOT}}/themes/custom/{self.theme_name}"
echo "  - Run 'npm run watch' for development"
echo ""
"""
        
        filepath = self.output_dir / "install.sh"
        with open(filepath, 'w') as f:
            f.write(content)
        
        # Make script executable
        import stat
        filepath.chmod(filepath.stat().st_mode | stat.S_IEXEC)
        
        return str(filepath)
    
    def _generate_install_guide(self) -> str:
        """Generate detailed installation guide."""
        content = f"""# Installation Guide: {self.theme_label}

## Quick Start

For automated installation on Unix/Linux/Mac:

```bash
chmod +x install.sh
./install.sh
```

## Manual Installation

### Prerequisites

1. **Drupal 10 or 11** installed and running
2. **Node.js** (v16 or higher) - [Download](https://nodejs.org/)
3. **Composer** - [Download](https://getcomposer.org/)
4. **Drush** (optional but recommended) - `composer require drush/drush`

### Step 1: Install Bootstrap Barrio

Bootstrap Barrio is the base theme that {self.theme_label} extends.

#### Method A: Composer (Recommended)

```bash
# From your Drupal root directory
composer require drupal/bootstrap_barrio:^5.5
```

#### Method B: Drush

```bash
drush en bootstrap_barrio -y
```

#### Method C: Drupal UI

1. Go to **Extend** (`/admin/modules`)
2. Search for "Bootstrap Barrio"
3. Install and enable
4. Or download from [drupal.org/project/bootstrap_barrio](https://www.drupal.org/project/bootstrap_barrio)

#### Method D: Manual Download

1. Download from https://www.drupal.org/project/bootstrap_barrio
2. Extract to `themes/contrib/bootstrap_barrio`
3. Enable via Appearance page

### Step 2: Install Theme Files

Copy this theme to your Drupal installation:

```bash
# From where you generated this theme
cp -r {self.theme_name} /path/to/drupal/themes/custom/

# Or if using Composer-based Drupal
cp -r {self.theme_name} /path/to/drupal/web/themes/custom/
```

### Step 3: Install Dependencies

Navigate to the theme directory and install Node.js packages:

```bash
cd /path/to/drupal/themes/custom/{self.theme_name}
npm install
```

### Step 4: Compile Assets

Compile SASS to CSS:

```bash
npm run compile
```

### Step 5: Enable the Theme

#### Using Drush (Recommended)

```bash
# From Drupal root
drush theme:enable {self.theme_name}
drush config-set system.theme default {self.theme_name} -y
drush cr
```

#### Using Drupal UI

1. Navigate to **Appearance** (`/admin/appearance`)
2. Find "{self.theme_label}"
3. Click **Install and set as default**
4. Clear cache: **Configuration** → **Performance** → **Clear all caches**

### Step 6: Verify Installation

1. Visit your site's homepage
2. Check that the theme is active
3. Verify CSS is loading (check browser console for errors)

## Troubleshooting

### Bootstrap Barrio Not Found

**Error:** "The selected base theme bootstrap_barrio is not installed"

**Solution:**
```bash
composer require drupal/bootstrap_barrio:^5.5
drush cr
```

### CSS Not Loading

**Problem:** Styles don't appear on the site

**Solution:**
```bash
cd themes/custom/{self.theme_name}
npm run compile
drush cr
```

### Permission Errors

**Problem:** Cannot write compiled CSS

**Solution:**
```bash
chmod -R 755 themes/custom/{self.theme_name}
chown -R www-data:www-data themes/custom/{self.theme_name}
```

### Node Modules Not Found

**Problem:** `npm run compile` fails

**Solution:**
```bash
rm -rf node_modules package-lock.json
npm install
npm run compile
```

## Development Workflow

### Watching for Changes

During development, use watch mode to automatically compile SASS:

```bash
npm run watch
```

This will monitor your SASS files and recompile on changes.

### Clearing Cache

After making template changes, clear Drupal cache:

```bash
drush cr
# or
drush cache:rebuild
```

### Debugging

Enable Twig debugging in `development.services.yml`:

```yaml
parameters:
  twig.config:
    debug: true
    auto_reload: true
    cache: false
```

## Production Deployment

Before deploying to production:

1. **Compile assets:**
   ```bash
   npm run compile
   ```

2. **Clear cache:**
   ```bash
   drush cr
   ```

3. **Export configuration:**
   ```bash
   drush config:export -y
   ```

4. **Do NOT commit:**
   - `node_modules/`
   - `.sass-cache/`
   - `*.css.map` (optional)

## File Structure

```
{self.theme_name}/
├── {self.theme_name}.info.yml        # Theme metadata
├── {self.theme_name}.libraries.yml   # Asset libraries
├── {self.theme_name}.theme           # PHP preprocessing
├── composer.json                     # PHP dependencies
├── package.json                      # Node.js dependencies
├── gulpfile.js                       # Build configuration
├── install.sh                        # Auto-install script
├── README.md                         # Documentation
├── INSTALL.md                        # This file
├── css/                              # Compiled CSS (generated)
│   ├── style.css
│   └── fonts.css
├── sass/                             # SASS source files
│   ├── style.scss
│   ├── abstracts/
│   ├── base/
│   ├── components/
│   ├── layout/
│   └── pages/
├── js/                               # JavaScript files
│   └── global.js
├── templates/                        # Twig templates
│   ├── layout/
│   ├── content/
│   ├── paragraph/
│   └── field/
└── images/                           # Theme images
    └── icon/
```

## Support

- **Drupal Documentation:** https://www.drupal.org/docs/theming-drupal
- **Bootstrap Barrio:** https://www.drupal.org/project/bootstrap_barrio
- **Sass Documentation:** https://sass-lang.com/documentation

## License

GPL-2.0-or-later
"""
        
        filepath = self.output_dir / "INSTALL.md"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _save_css(self, css_content: str) -> str:
        """Save compiled CSS file."""
        filepath = self.output_dir / "css" / "style.css"
        with open(filepath, 'w') as f:
            f.write(css_content)
        
        return str(filepath)
    
    def _generate_fonts_css(self) -> str:
        """Generate fonts CSS file."""
        content = """/* Font Definitions */

/* Add custom web fonts here */
/* Example:
@font-face {
  font-family: 'CustomFont';
  src: url('../fonts/customfont.woff2') format('woff2'),
       url('../fonts/customfont.woff') format('woff');
  font-weight: normal;
  font-style: normal;
  font-display: swap;
}
*/

/* System font stack is used by default in variables */
"""
        filepath = self.output_dir / "css" / "fonts.css"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _validate_scss_content(self, content: str) -> str:
        """Validate and clean SCSS content to prevent compilation errors.
        
        Removes:
        - Status messages that don't belong in SCSS
        - Non-SCSS console output
        - Invalid syntax patterns
        """
        import re
        
        # Remove status/console messages
        invalid_patterns = [
            r'CSS boilerplate generated.*',
            r'saved to:.*',
            r'^\s*[A-Z][a-z]+ [a-z]+ generated.*$',
            r'\u2713.*',  # Checkmark symbols
        ]
        
        cleaned = content
        for pattern in invalid_patterns:
            cleaned = re.sub(pattern, '', cleaned, flags=re.MULTILINE)
        
        # Remove any lines that look like file paths
        cleaned = re.sub(r'^\s*/[^\s]+\.(scss|css)\s*$', '', cleaned, flags=re.MULTILINE)
        
        # Clean up multiple blank lines
        cleaned = re.sub(r'\n{3,}', '\n\n', cleaned)
        
        return cleaned.strip()
    
    def _generate_main_scss(self, css_content: str) -> str:
        """Generate main SCSS file that imports all partials."""
        # Validate and clean CSS content
        cleaned_css = self._validate_scss_content(css_content)
        
        content = f"""// {self.theme_label} - Main Stylesheet
// Generated from Figma design

// Abstracts - using modern @use syntax
@use 'abstracts/variables' as *;
@use 'abstracts/mixins' as *;
@use 'abstracts/breakpoints' as *;

// Base
@use 'base/reset';
@use 'base/base';
@use 'base/buttons';

// Layout
@use 'layout/navbar';
@use 'layout/footer';

// Components
@use 'components/component-text';
@use 'components/component-image';
@use 'components/component-card';

// Pages
@use 'pages/page';

// Extracted from Figma design
{cleaned_css}
"""
        
        filepath = self.output_dir / "sass" / "style.scss"
        with open(filepath, 'w') as f:
            f.write(content)
        
        # Also generate the page SCSS
        page_scss_path = self._generate_page_scss()
        
        return str(filepath)
    
    def _generate_scss_abstracts(self) -> Dict[str, str]:
        """Generate SCSS abstract files (variables, mixins, breakpoints)."""
        files = {}
        
        # Variables
        variables_content = """/* Design Tokens - CSS Variables */
:root {
  // Colors
  --color-primary: #007bff;
  --color-secondary: #6c757d;
  --color-success: #28a745;
  --color-danger: #dc3545;
  --color-warning: #ffc107;
  --color-info: #17a2b8;
  --color-light: #f8f9fa;
  --color-dark: #343a40;
  
  // Typography
  --font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-family-heading: var(--font-family-base);
  
  --font-size-base: 16px;
  --font-size-h1: 2.5rem;
  --font-size-h2: 2rem;
  --font-size-h3: 1.75rem;
  --font-size-h4: 1.5rem;
  --font-size-h5: 1.25rem;
  --font-size-h6: 1rem;
  
  --font-weight-normal: 400;
  --font-weight-bold: 700;
  
  // Spacing
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
  --spacing-xxl: 3rem;
  
  // Layout
  --max-width: 1680px;
  --container-padding: 15px;
}
"""
        
        variables_path = self.output_dir / "sass" / "abstracts" / "_variables.scss"
        with open(variables_path, 'w') as f:
            f.write(variables_content)
        files['sass/abstracts/_variables.scss'] = str(variables_path)
        
        # Mixins
        mixins_content = """// Mixins

@mixin responsive($breakpoint) {
  @if $breakpoint == mobile {
    @media (max-width: 767px) { @content; }
  }
  @else if $breakpoint == tablet {
    @media (min-width: 768px) and (max-width: 1023px) { @content; }
  }
  @else if $breakpoint == desktop {
    @media (min-width: 1024px) { @content; }
  }
}

@mixin flex-center {
  display: flex;
  align-items: center;
  justify-content: center;
}

@mixin clearfix {
  &::after {
    content: "";
    display: table;
    clear: both;
  }
}
"""
        
        mixins_path = self.output_dir / "sass" / "abstracts" / "_mixins.scss"
        with open(mixins_path, 'w') as f:
            f.write(mixins_content)
        files['sass/abstracts/_mixins.scss'] = str(mixins_path)
        
        # Breakpoints
        breakpoints_content = """// Responsive Breakpoints

$breakpoint-mobile: 767px;
$breakpoint-tablet: 768px;
$breakpoint-desktop: 1024px;
$breakpoint-wide: 1440px;
"""
        
        breakpoints_path = self.output_dir / "sass" / "abstracts" / "_breakpoints.scss"
        with open(breakpoints_path, 'w') as f:
            f.write(breakpoints_content)
        files['sass/abstracts/_breakpoints.scss'] = str(breakpoints_path)
        
        # Generate base SCSS files
        files.update(self._generate_scss_base())
        
        # Generate component SCSS files
        files.update(self._generate_scss_components())
        
        # Generate layout SCSS files
        files.update(self._generate_scss_layout())
        
        return files
    
    def _generate_scss_base(self) -> Dict[str, str]:
        """Generate SCSS base files."""
        files = {}
        
        # Reset
        reset_content = """/* CSS Reset */
*, *::before, *::after {
  box-sizing: border-box;
}

* {
  margin: 0;
  padding: 0;
}

html {
  font-size: 16px;
}

body {
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

img, picture, video, canvas, svg {
  display: block;
  max-width: 100%;
}

input, button, textarea, select {
  font: inherit;
}

p, h1, h2, h3, h4, h5, h6 {
  overflow-wrap: break-word;
}
"""
        reset_path = self.output_dir / "sass" / "base" / "_reset.scss"
        with open(reset_path, 'w') as f:
            f.write(reset_content)
        files['sass/base/_reset.scss'] = str(reset_path)
        
        # Base
        base_content = """/* Base Styles */
body {
  font-family: var(--font-family-base);
  font-size: var(--font-size-base);
  color: var(--color-dark);
  background-color: var(--color-light);
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-family-heading);
  font-weight: var(--font-weight-bold);
  line-height: 1.2;
  margin-bottom: var(--spacing-md);
}

h1 { font-size: var(--font-size-h1); }
h2 { font-size: var(--font-size-h2); }
h3 { font-size: var(--font-size-h3); }
h4 { font-size: var(--font-size-h4); }
h5 { font-size: var(--font-size-h5); }
h6 { font-size: var(--font-size-h6); }

p {
  margin-bottom: var(--spacing-md);
}

a {
  color: var(--color-primary);
  text-decoration: none;
  
  &:hover {
    text-decoration: underline;
  }
}

.container {
  max-width: var(--max-width);
  margin: 0 auto;
  padding: 0 var(--container-padding);
}
"""
        base_path = self.output_dir / "sass" / "base" / "_base.scss"
        with open(base_path, 'w') as f:
            f.write(base_content)
        files['sass/base/_base.scss'] = str(base_path)
        
        # Buttons
        buttons_content = """/* Button Styles */
.btn {
  display: inline-block;
  padding: var(--spacing-sm) var(--spacing-lg);
  border-radius: 4px;
  font-weight: var(--font-weight-bold);
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  &--primary {
    background-color: var(--color-primary);
    color: white;
    
    &:hover {
      filter: brightness(0.9);
    }
  }
  
  &--secondary {
    background-color: var(--color-secondary);
    color: white;
    
    &:hover {
      filter: brightness(0.9);
    }
  }
}
"""
        buttons_path = self.output_dir / "sass" / "base" / "_buttons.scss"
        with open(buttons_path, 'w') as f:
            f.write(buttons_content)
        files['sass/base/_buttons.scss'] = str(buttons_path)
        
        return files
    
    def _generate_scss_components(self) -> Dict[str, str]:
        """Generate SCSS component files."""
        files = {}
        
        # Component - Text
        text_content = """/* Text Component */
.component-text {
  margin-bottom: var(--spacing-lg);
  
  &__title {
    margin-bottom: var(--spacing-md);
  }
  
  &__content {
    line-height: 1.8;
  }
}
"""
        text_path = self.output_dir / "sass" / "components" / "_component-text.scss"
        with open(text_path, 'w') as f:
            f.write(text_content)
        files['sass/components/_component-text.scss'] = str(text_path)
        
        # Component - Image
        image_content = """/* Image Component */
.component-image {
  margin-bottom: var(--spacing-lg);
  
  .image-container {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    
    img {
      width: 100%;
      height: auto;
      display: block;
    }
  }
  
  .text-content {
    margin-top: var(--spacing-md);
  }
}

.background-image-container {
  position: relative;
  background-size: cover;
  background-position: center;
  
  &.with-text {
    .position-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 100%;
    }
  }
}
"""
        image_path = self.output_dir / "sass" / "components" / "_component-image.scss"
        with open(image_path, 'w') as f:
            f.write(image_content)
        files['sass/components/_component-image.scss'] = str(image_path)
        
        # Component - Card
        card_content = """/* Card Component */
.component-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  
  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  }
  
  &__image {
    margin-bottom: var(--spacing-md);
    
    img {
      width: 100%;
      border-radius: 4px;
    }
  }
  
  &__title {
    margin-bottom: var(--spacing-sm);
  }
  
  &__content {
    color: var(--color-dark);
  }
}
"""
        card_path = self.output_dir / "sass" / "components" / "_component-card.scss"
        with open(card_path, 'w') as f:
            f.write(card_content)
        files['sass/components/_component-card.scss'] = str(card_path)
        
        return files
    
    def _generate_scss_layout(self) -> Dict[str, str]:
        """Generate SCSS layout files."""
        files = {}
        
        # Navbar
        navbar_content = """@use '../abstracts/mixins' as *;
@use '../abstracts/breakpoints' as *;

/* Navigation Bar */
.navbar {
  background-color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: var(--spacing-md) 0;
  
  .navbar-toggler {
    display: none;
    
    @include responsive(mobile) {
      display: block;
    }
  }
  
  .navbar-nav {
    display: flex;
    gap: var(--spacing-lg);
    list-style: none;
    
    @include responsive(mobile) {
      flex-direction: column;
    }
  }
  
  .nav-link {
    color: var(--color-dark);
    font-weight: var(--font-weight-normal);
    
    &:hover {
      color: var(--color-primary);
    }
  }
}
"""
        navbar_path = self.output_dir / "sass" / "layout" / "_navbar.scss"
        with open(navbar_path, 'w') as f:
            f.write(navbar_content)
        files['sass/layout/_navbar.scss'] = str(navbar_path)
        
        # Footer
        footer_content = """/* Footer */
.site-footer {
  background-color: var(--color-dark);
  color: white;
  padding: var(--spacing-xxl) 0 var(--spacing-lg);
  margin-top: var(--spacing-xxl);
  
  .footer-column {
    margin-bottom: var(--spacing-lg);
    
    h3 {
      margin-bottom: var(--spacing-md);
      font-size: var(--font-size-h5);
    }
    
    ul {
      list-style: none;
      
      li {
        margin-bottom: var(--spacing-sm);
      }
    }
    
    a {
      color: rgba(255, 255, 255, 0.8);
      
      &:hover {
        color: white;
      }
    }
  }
}
"""
        footer_path = self.output_dir / "sass" / "layout" / "_footer.scss"
        with open(footer_path, 'w') as f:
            f.write(footer_content)
        files['sass/layout/_footer.scss'] = str(footer_path)
        
        return files
    
    def _generate_page_scss(self) -> str:
        """Generate page-specific SCSS."""
        content = """/* Page Styles */
.page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-container {
  flex: 1;
  padding: var(--spacing-lg) 0;
}

.node {
  &__content {
    margin-bottom: var(--spacing-lg);
  }
  
  &__image {
    margin-bottom: var(--spacing-md);
  }
  
  &__body {
    line-height: 1.8;
  }
}

.paragraph {
  margin-bottom: var(--spacing-lg);
  
  &:last-child {
    margin-bottom: 0;
  }
}
"""
        filepath = self.output_dir / "sass" / "pages" / "_page.scss"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _generate_twig_templates(self, figma_data: Dict[str, Any], exported_images: Optional[Dict[str, str]] = None, html_content: str = "") -> Dict[str, str]:
        """Generate Twig template files."""
        files = {}
        
        # Extract design name from Figma data
        design_name = figma_data.get('name', 'Page') if figma_data else 'Page'
        safe_design_name = design_name.lower().replace(' ', '-').replace('_', '-')
        
        # Generate page.html.twig
        page_template = self._generate_page_twig(design_name, figma_data, exported_images, html_content)
        page_path = self.output_dir / "templates" / "layout" / "page.html.twig"
        with open(page_path, 'w') as f:
            f.write(page_template)
        files['templates/layout/page.html.twig'] = str(page_path)
        
        # Generate content type specific template
        node_template = self._generate_node_twig(design_name, figma_data, exported_images)
        node_path = self.output_dir / "templates" / "content" / f"node--{safe_design_name}.html.twig"
        with open(node_path, 'w') as f:
            f.write(node_template)
        files[f'templates/content/node--{safe_design_name}.html.twig'] = str(node_path)
        
        # Generate paragraph templates for common components
        paragraph_templates = self._generate_paragraph_templates(figma_data, exported_images)
        for template_name, template_content in paragraph_templates.items():
            para_path = self.output_dir / "templates" / "paragraph" / f"{template_name}.html.twig"
            with open(para_path, 'w') as f:
                f.write(template_content)
            files[f'templates/paragraph/{template_name}.html.twig'] = str(para_path)
        
        return files
    
    def _generate_page_twig(self, design_name: str, figma_data: Dict[str, Any], exported_images: Optional[Dict[str, str]] = None, html_content: str = "") -> str:
        """Generate main page.html.twig template."""
        
        # If we have smart HTML content, use it!
        content_html = "{{ page.content }}"
        if html_content:
            # Extract body content from the full HTML
            import re
            body_match = re.search(r'<div id="figma-root">(.*?)</div>', html_content, re.DOTALL)
            if body_match:
                content_html = body_match.group(1)
            else:
                # Fallback if regex fails (e.g. if structure changed)
                content_html = html_content

        return f"""{{%extends "@bootstrap_barrio/layout/page.html.twig" %}}
{{#
/**
 * @file
 * Theme implementation to display a page - {design_name}
 * Generated from Figma design
 *
 * Available regions:
 * - page.header: Header region
 * - page.primary_menu: Primary menu
 * - page.content: Main content
 * - page.footer_first: Footer first column
 * - page.footer_second: Footer second column
 * - page.footer_third: Footer third column
 */
#}}

{{%block head %}}
  {{{{page.header}}}}
  
  <nav{{{{navbar_attributes}}}}>
    {{% if container_navbar %}}
      <div class="container">
    {{% endif %}}
      {{{{page.primary_menu}}}}
    {{% if container_navbar %}}
      </div>
    {{% endif %}}
  </nav>
{{%endblock%}}

{{%block content %}}
  <div id="main" class="main-container {{{{container}}}} js-quickedit-main-content">
    <div class="row">
      {{% if page.sidebar_first %}}
        <aside class="col-md-3" role="complementary">
          {{{{page.sidebar_first}}}}
        </aside>
      {{% endif %}}
      
      <section{{{{content_attributes}}}}>
        {content_html}
      </section>
      
      {{% if page.sidebar_second %}}
        <aside class="col-md-3" role="complementary">
          {{{{page.sidebar_second}}}}
        </aside>
      {{% endif %}}
    </div>
  </div>
{{%endblock%}}

{{%block footer %}}
  {{% if page.footer_first or page.footer_second or page.footer_third %}}
    <footer class="site-footer">
      <div class="container">
        <div class="row">
          {{% if page.footer_first %}}
            <div class="col-md-4">
              {{{{page.footer_first}}}}
            </div>
          {{% endif %}}
          {{% if page.footer_second %}}
            <div class="col-md-4">
              {{{{page.footer_second}}}}
            </div>
          {{% endif %}}
          {{% if page.footer_third %}}
            <div class="col-md-4">
              {{{{page.footer_third}}}}
            </div>
          {{% endif %}}
        </div>
      </div>
    </footer>
  {{% endif %}}
{{%endblock%}}
"""
    
    def _generate_node_twig(self, design_name: str, figma_data: Dict[str, Any], exported_images: Optional[Dict[str, str]] = None) -> str:
        """Generate node content template."""
        return f"""{{#
/**
 * @file
 * Theme implementation for {design_name} node
 * Generated from Figma design
 *
 * Available variables:
 * - node: Full node entity
 * - label: Node title
 * - content: All node items
 * - attributes: HTML attributes
 */
#}}
{{{{attach_library('{self.theme_name}/global-styling')}}}}

{{%
  set classes = [
    'node',
    'node--type-' ~ node.bundle|clean_class,
    node.isPromoted() ? 'node--promoted',
    not node.isPublished() ? 'node--unpublished',
    view_mode ? 'node--view-mode-' ~ view_mode|clean_class,
  ]
%}}

<article{{{{attributes.addClass(classes)}}}}>
  {{%block node_header %}}
    {{% if label and not page %}}
      <h2{{{{title_attributes}}}}>
        <a href="{{{{url}}}}" rel="bookmark">{{{{label}}}}</a>
      </h2>
    {{% endif %}}
  {{%endblock%}}

  {{%block node_content %}}
    <div{{{{content_attributes.addClass('node__content')}}}}>
      {{% if content.field_image|render %}}
        <div class="node__image">
          {{{{content.field_image}}}}
        </div>
      {{% endif %}}
      
      <div class="node__body">
        {{{{content|without('field_image')}}}}
      </div>
    </div>
  {{%endblock%}}
</article>
"""
    
    def _generate_paragraph_templates(self, figma_data: Dict[str, Any], exported_images: Optional[Dict[str, str]] = None) -> Dict[str, str]:
        """Generate paragraph component templates."""
        templates = {}
        
        # Text component
        templates['paragraph--c-text'] = f"""{{#
/**
 * @file
 * Paragraph: Text component
 * Generated from Figma design
 */
#}}
{{%set classes = [
  'paragraph',
  'paragraph--type--' ~ paragraph.bundle|clean_class,
  'paragraph--c-text'
]%}}

<div{{{{attributes.addClass(classes)}}}}>
  {{%block content %}}
    {{{{content}}}}
  {{%endblock%}}
</div>
"""
        
        # Image component
        templates['paragraph--c-image'] = f"""{{#
/**
 * @file
 * Paragraph: Image component
 * Generated from Figma design
 */
#}}
{{%set classes = [
  'paragraph',
  'paragraph--type--' ~ paragraph.bundle|clean_class,
  'paragraph--c-image'
]%}}

<div{{{{attributes.addClass(classes)}}}}>
  {{%block content %}}
    {{% if content.field_c_image|render %}}
      <div class="image-container">
        {{{{content.field_c_image}}}}
      </div>
    {{% endif %}}
    
    {{% set text_content = content|without('field_c_image')|render %}}
    {{% if text_content|trim is not empty %}}
      <div class="text-content">
        {{{{text_content}}}}
      </div>
    {{% endif %}}
  {{%endblock%}}
</div>
"""
        
        return templates
    
    def _generate_global_js(self) -> str:
        """Generate global JavaScript file."""
        content = f"""/**
 * @file
 * Global JavaScript for {self.theme_label}
 * Generated from Figma design
 */

(function ($, Drupal) {{
  'use strict';

  Drupal.behaviors.{self.theme_name}Global = {{
    attach: function (context, settings) {{
      // Add custom JavaScript behaviors here
      console.log('{self.theme_label} theme loaded');
    }}
  }};

}})(jQuery, Drupal);
"""
        
        filepath = self.output_dir / "js" / "global.js"
        with open(filepath, 'w') as f:
            f.write(content)
        
        return str(filepath)
    
    def _organize_images(self, exported_images: Dict[str, str]) -> Dict[str, str]:
        """Organize exported images into theme structure."""
        import shutil
        
        files = {}
        
        for purpose, source_path in exported_images.items():
            if not os.path.exists(source_path):
                continue
            
            # Determine destination based on image purpose
            filename = os.path.basename(source_path)
            
            if 'icon' in purpose.lower():
                dest_dir = self.output_dir / 'images' / 'icon'
            else:
                dest_dir = self.output_dir / 'images'
            
            dest_dir.mkdir(parents=True, exist_ok=True)
            dest_path = dest_dir / filename
            
            # Copy file
            shutil.copy2(source_path, dest_path)
            files[f'images/{filename}'] = str(dest_path)
        
        return files
    
    def generate_summary(self, generated_files: Dict[str, str]) -> str:
        """Generate a summary report of the theme generation."""
        summary = f"""
================================================================================
DRUPAL THEME GENERATION COMPLETE
================================================================================

Theme Name: {self.theme_label}
Machine Name: {self.theme_name}
Output Directory: {self.output_dir}

GENERATED FILES:
--------------------------------------------------------------------------------
"""
        
        # Group files by type
        file_groups = {
            'Configuration': [],
            'Templates': [],
            'Stylesheets': [],
            'JavaScript': [],
            'Images': [],
            'Documentation': []
        }
        
        for rel_path, abs_path in generated_files.items():
            if rel_path.endswith(('.yml', '.theme', 'composer.json', 'package.json', 'gulpfile.js')):
                file_groups['Configuration'].append(rel_path)
            elif '.twig' in rel_path:
                file_groups['Templates'].append(rel_path)
            elif rel_path.endswith(('.css', '.scss')):
                file_groups['Stylesheets'].append(rel_path)
            elif rel_path.endswith('.js'):
                file_groups['JavaScript'].append(rel_path)
            elif 'images/' in rel_path:
                file_groups['Images'].append(rel_path)
            elif rel_path.endswith('.md'):
                file_groups['Documentation'].append(rel_path)
        
        for group, files in file_groups.items():
            if files:
                summary += f"\n{group}:\n"
                for file in sorted(files):
                    summary += f"  ✓ {file}\n"
        
        summary += f"""
================================================================================
NEXT STEPS:
================================================================================

1. Install dependencies:
   cd {self.output_dir}
   npm run install-tools

2. Compile SASS:
   npm run compile

3. Install theme in Drupal:
   - Copy the theme folder to: /themes/custom/{self.theme_name}
   - Enable the theme in Drupal admin

4. Customize as needed:
   - Edit SASS files in sass/ directory
   - Modify Twig templates in templates/ directory
   - Add custom functionality in {self.theme_name}.theme

================================================================================
"""
        
        return summary

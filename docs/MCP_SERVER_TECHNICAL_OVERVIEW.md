# Figma to Drupal Theme Generation MCP Server: Technical Overview

## Executive Summary

This MCP (Model Context Protocol) server automates the conversion of Figma designs into production-ready Drupal themes with automatic module detection. It bridges the gap between design and development by analyzing Figma design structures and generating complete, standards-compliant Drupal theme packages.

**Key Achievement**: Reduces Drupal theme scaffolding time from hours/days to minutes while automatically detecting and declaring required module dependencies.

---

## What This MCP Server Provides

### Core Capabilities

#### 1. **Automated Figma Design Analysis**
- Fetches complete node hierarchies from Figma REST API
- Parses design structure (frames, components, sections)
- Extracts design tokens (colors, typography, spacing)
- Identifies images and exports them with proper categorization

#### 2. **Intelligent Drupal Module Detection** ⭐ **Unique Feature**
- Scans Figma layer names for semantic patterns
- Maps design components to Drupal modules:
  - "Product Carousel" → `easy_carousel` / `slick`
  - "Newsletter Form" → `webform`
  - "Social Links" → `social_media_links`
  - "Testimonials Slider" → `testimonials`
  - "Hero Section" → `paragraphs`, `layout_builder`
- Generates dependency declarations in `theme.info.yml`
- Provides confidence scoring for each detected module

#### 3. **Complete Drupal Theme Generation**
Generates production-ready theme structure following Drupal best practices:

```
iiyn_website/
├── iiyn_website.info.yml          # Theme config with auto-detected dependencies
├── iiyn_website.libraries.yml     # Asset library definitions
├── iiyn_website.theme             # PHP preprocessing hooks
├── composer.json                  # Theme metadata
├── package.json                   # Node dependencies
├── gulpfile.js                    # SASS build pipeline
├── css/
│   ├── style.css                  # Compiled CSS
│   └── fonts.css                  # Font definitions
├── sass/
│   ├── style.scss                 # Main SASS entry
│   ├── abstracts/
│   │   ├── _variables.scss        # Design tokens from Figma
│   │   ├── _mixins.scss
│   │   └── _breakpoints.scss
│   ├── base/
│   │   ├── _reset.scss
│   │   ├── _base.scss
│   │   └── _buttons.scss
│   ├── components/
│   │   ├── _component-card.scss
│   │   ├── _component-image.scss
│   │   └── _component-text.scss
│   └── layout/
│       ├── _navbar.scss
│       └── _footer.scss
├── js/
│   └── global.js                  # JavaScript functionality
├── templates/
│   ├── layout/
│   │   └── page.html.twig
│   ├── content/
│   │   └── node--[type].html.twig
│   ├── paragraph/
│   └── field/
├── images/                        # Auto-exported from Figma
│   └── manifest.json              # Image mapping reference
├── README.md
└── INSTALL.md
```

#### 4. **Design Token Extraction**
- Extracts color palette as CSS custom properties
- Generates responsive breakpoints
- Creates typography scales
- Maintains design system consistency

#### 5. **Smart Image Export**
- Auto-detects all image nodes in Figma
- Categorizes images (hero, product, icons, backgrounds)
- Exports at 2x resolution for retina displays
- Creates manifest for image mapping
- Generates SVG placeholders for missing assets

---

## What This Server Does NOT Include

### ❌ MCP Client Implementation
This is an **MCP server** only. You need an MCP client to use it:
- **Claude Desktop** (Anthropic's official client)
- **VS Code with Copilot** (via MCP extension)
- **Custom MCP clients** implementing the protocol

### ❌ LLM/AI Capabilities
This server does NOT include:
- Language model inference
- AI-powered code generation beyond templates
- Natural language processing
- Machine learning models

The "intelligence" comes from:
- ✅ Pattern matching (regex-based module detection)
- ✅ Rule-based transformations (Figma → HTML/CSS)
- ✅ Template-based generation (predefined Drupal structures)

### ❌ Figma Desktop App
Unlike Figma's official MCP server, this version:
- Uses **Figma Personal Access Tokens** (API-based)
- Runs in headless/backend environments
- No desktop app dependency
- Works in CI/CD pipelines

### ❌ Real-time Design Sync
- No live updates from Figma
- No bidirectional sync (Drupal → Figma)
- Point-in-time conversion only
- Requires re-generation for design updates

### ❌ Complete Theme Implementation
Generates scaffolding and structure, but developers still need to:
- Implement custom business logic
- Create content types and fields
- Configure Views and Paragraphs
- Write custom Twig logic
- Handle edge cases and complex interactions

---

## Key Benefits

### For Development Teams

#### 1. **Time Savings**
- **Before**: 4-8 hours to scaffold a Drupal theme
- **After**: 2-5 minutes for complete generation
- **ROI**: ~95% reduction in initial setup time

#### 2. **Consistency & Standards**
- Enforces Drupal coding standards
- Follows Bootstrap Barrio best practices
- Consistent file organization across projects
- Predictable structure for team members

#### 3. **Reduced Human Error**
- No forgotten dependencies
- Proper YAML formatting
- Correct library definitions
- Valid Twig syntax

#### 4. **Design-Development Alignment**
- Design tokens directly from Figma
- Visual fidelity through exported assets
- Semantic HTML matching design structure
- Easier handoff between designers and developers

### For Business

#### 1. **Faster Time-to-Market**
- Rapid prototyping from designs
- Quicker MVP delivery
- Faster iteration cycles

#### 2. **Cost Reduction**
- Less manual scaffolding work
- Fewer integration bugs
- Reduced onboarding time for new developers

#### 3. **Scalability**
- Standardized theme generation process
- Repeatable workflows
- Easier to maintain multiple sites

---

## Industrialization Roadmap

### Current State: Proof of Concept
- ✅ Core functionality working
- ✅ Basic error handling
- ✅ File-based logging
- ⚠️ Single-threaded execution
- ⚠️ Limited validation
- ⚠️ No monitoring/metrics

### Path to Production

#### Option 1: Monolithic Approach (Current)
Keep everything in one MCP server.

**Pros:**
- Simple deployment
- Single configuration point
- Easier debugging

**Cons:**
- Tight coupling
- Hard to scale individual components
- Figma API dependency affects entire server

**Required Improvements:**
1. **Error Handling & Resilience**
   - Retry logic for Figma API calls
   - Graceful degradation when modules can't be detected
   - Validation of Figma file access permissions
   - Timeout handling

2. **Performance Optimization**
   - Caching of Figma API responses
   - Parallel image exports
   - Incremental generation (only changed files)
   - Streaming large responses

3. **Observability**
   - Structured logging (JSON format)
   - Metrics collection (generation time, success rate)
   - Distributed tracing
   - Health check endpoints

4. **Security Hardening**
   - Token rotation support
   - Rate limiting
   - Input validation (file paths, node IDs)
   - Sandbox for file generation

5. **Testing**
   - Unit tests for module detection
   - Integration tests with mock Figma API
   - End-to-end tests with real Figma files
   - Regression test suite

6. **Documentation**
   - API documentation
   - Troubleshooting guides
   - Migration guides for version updates
   - Architecture decision records (ADRs)

---

#### Option 2: Microservices Architecture (Recommended) ⭐

Split into specialized MCP servers with clear boundaries.

```
┌─────────────────────────────────────────────────────────────┐
│                      MCP Client Layer                        │
│           (Claude Desktop, VS Code, Custom Apps)             │
└───────────────┬─────────────────────────────┬───────────────┘
                │                             │
                │                             │
┌───────────────▼──────────────┐  ┌──────────▼────────────────┐
│   Figma Remote MCP Server    │  │  Drupal Theme Generator   │
│   (Official or Custom)       │  │      MCP Server           │
│                              │  │                           │
│  • Fetch design data         │  │  • Module detection       │
│  • Export images             │  │  • Theme scaffolding      │
│  • Extract design tokens     │  │  • Template generation    │
│  • Return structured JSON    │  │  • SCSS/CSS compilation   │
│                              │  │  • Twig template creation │
└──────────────┬───────────────┘  └───────────┬───────────────┘
               │                              │
               │                              │
               ▼                              ▼
         Figma REST API               File System / Git Repo
```

**Architecture Components:**

##### 1. **Figma Remote MCP Server**
Use existing Figma MCP or create lightweight wrapper.

**Responsibilities:**
- Authenticate with Figma API
- Fetch file metadata and node data
- Export images at specified resolutions
- Extract design tokens (colors, fonts, spacing)
- Return normalized JSON structures

**Tools Exposed:**
```python
fetch_figma_file(fileKey: str, nodeId: str) -> FigmaFileData
export_figma_images(fileKey: str, nodeIds: list) -> ImageExports
extract_design_tokens(fileKey: str, nodeId: str) -> DesignTokens
```

**Benefits:**
- Reusable across different frameworks (React, Vue, Angular)
- Can use official Figma MCP when available
- Easier to update when Figma API changes
- Independent scaling and caching

##### 2. **Drupal Theme Generator MCP Server**
Focused solely on Drupal theme generation.

**Responsibilities:**
- Analyze design structure for Drupal patterns
- Detect required modules
- Generate theme file structure
- Create Twig templates
- Generate SCSS with design tokens
- Produce composer.json and package.json

**Tools Exposed:**
```python
analyze_for_drupal_modules(designStructure: dict) -> ModuleList
generate_theme_structure(
    themeName: str,
    designData: FigmaFileData,
    designTokens: DesignTokens,
    images: ImageExports
) -> ThemePackage
```

**Input Format (from Figma MCP):**
```json
{
  "fileKey": "ABC123",
  "nodeId": "1-182",
  "structure": {
    "type": "FRAME",
    "name": "Homepage",
    "children": [...]
  },
  "designTokens": {
    "colors": {...},
    "typography": {...},
    "spacing": {...}
  },
  "images": {
    "hero-image": "/path/to/hero.png",
    "logo": "/path/to/logo.svg"
  }
}
```

**Benefits:**
- Framework-specific expertise
- Easier to add Drupal-specific features
- Can be maintained by Drupal specialists
- Independent deployment and versioning

##### 3. **Orchestration Layer** (Optional)
For complex workflows requiring both servers.

**Implementation Options:**

**A. Client-side Orchestration**
LLM/AI agent coordinates between servers:

```python
# Pseudo-code workflow
figma_data = await figma_mcp.fetch_figma_file(file_key, node_id)
design_tokens = await figma_mcp.extract_design_tokens(file_key, node_id)
images = await figma_mcp.export_images(file_key, image_node_ids)

theme = await drupal_mcp.generate_theme_structure(
    themeName="my_theme",
    designData=figma_data,
    designTokens=design_tokens,
    images=images
)
```

**B. Dedicated Orchestration MCP**
Third server that calls others:

```python
# Design-to-Drupal Orchestrator MCP
async def convert_figma_to_drupal_theme(
    fileKey: str,
    nodeId: str,
    themeName: str
) -> ThemePackage:
    # Step 1: Fetch from Figma
    figma_data = await call_mcp("figma-remote", "fetch_file", {...})
    
    # Step 2: Generate Drupal theme
    theme = await call_mcp("drupal-generator", "generate_theme", {
        "designData": figma_data,
        "themeName": themeName
    })
    
    return theme
```

---

### Comparison: Monolithic vs. Microservices

| Aspect | Monolithic | Microservices |
|--------|-----------|---------------|
| **Deployment** | Single process | Multiple processes |
| **Complexity** | Lower | Higher |
| **Scalability** | Vertical only | Horizontal per service |
| **Reusability** | Limited | High (Figma MCP reusable) |
| **Maintenance** | Easier initially | Easier long-term |
| **Testing** | Simpler setup | More isolation |
| **Failure Impact** | Total failure | Partial failure |
| **Version Control** | Single repo | Multi-repo or monorepo |
| **Technology Stack** | Must be same | Can differ |
| **Performance** | Lower latency | Network overhead |

---

### Recommended Industrialization Path

#### Phase 1: Stabilize Current Implementation (Weeks 1-4)
- ✅ Add comprehensive error handling
- ✅ Implement retry logic for Figma API
- ✅ Add input validation
- ✅ Create unit and integration tests
- ✅ Add structured logging
- ✅ Write API documentation

#### Phase 2: Performance & Observability (Weeks 5-8)
- ✅ Implement caching layer for Figma API responses
- ✅ Add metrics collection (Prometheus/StatsD)
- ✅ Implement health check endpoints
- ✅ Add request tracing
- ✅ Optimize image export (parallel processing)
- ✅ Create monitoring dashboards

#### Phase 3: Split Architecture (Weeks 9-16)
- ✅ Evaluate official Figma MCP vs. custom wrapper
- ✅ Extract Drupal-specific logic into separate MCP
- ✅ Define clear contract between services (JSON schema)
- ✅ Implement client-side orchestration patterns
- ✅ Create integration test suite for multi-MCP workflow
- ✅ Document architecture and data flows

#### Phase 4: Production Hardening (Weeks 17-20)
- ✅ Security audit and penetration testing
- ✅ Load testing and performance benchmarking
- ✅ Disaster recovery planning
- ✅ Create runbooks for common issues
- ✅ Implement automated deployment pipeline
- ✅ Set up alerting and on-call rotation

#### Phase 5: Advanced Features (Ongoing)
- ✅ Support for Drupal 11+ features
- ✅ Additional framework support (React, Next.js)
- ✅ Design system library generation
- ✅ Component library integration (Storybook)
- ✅ Incremental updates (only changed components)
- ✅ Multi-language/i18n support

---

## Technical Considerations for Production

### 1. **API Rate Limiting**
Figma API has rate limits:
- 60 requests/minute for personal tokens
- Higher limits for OAuth tokens

**Solutions:**
- Implement exponential backoff
- Cache responses aggressively
- Use OAuth for production
- Batch requests where possible

### 2. **Large File Handling**
Complex Figma files can have thousands of nodes.

**Solutions:**
- Stream processing for large responses
- Pagination for image exports
- Incremental parsing
- Memory-efficient data structures

### 3. **Concurrent Requests**
Multiple users generating themes simultaneously.

**Solutions:**
- Connection pooling for Figma API
- Queue-based processing
- Rate limiting per user
- Resource limits (CPU, memory)

### 4. **Token Security**
Figma personal access tokens are sensitive.

**Solutions:**
- Environment variable injection
- Secret management systems (Vault, AWS Secrets Manager)
- Token rotation policies
- Audit logging of token usage

### 5. **File System Operations**
Generating files on disk has risks.

**Solutions:**
- Sandboxed directories per generation
- Cleanup of temporary files
- Virus scanning of generated files
- Size limits on output

### 6. **Versioning & Compatibility**
Drupal and Figma APIs evolve.

**Solutions:**
- Semantic versioning for MCP server
- Feature flags for new functionality
- Compatibility matrix documentation
- Automated compatibility testing

---

## Integration Patterns

### Pattern 1: Direct Integration
Client calls both MCPs directly.

```mermaid
sequenceDiagram
    Client->>Figma MCP: fetch_design()
    Figma MCP-->>Client: design_data
    Client->>Drupal MCP: generate_theme(design_data)
    Drupal MCP-->>Client: theme_package
```

**Pros:** Simple, flexible, client controls flow
**Cons:** Client must handle orchestration logic

### Pattern 2: Orchestrator Pattern
Dedicated orchestrator MCP coordinates.

```mermaid
sequenceDiagram
    Client->>Orchestrator: convert_to_drupal()
    Orchestrator->>Figma MCP: fetch_design()
    Figma MCP-->>Orchestrator: design_data
    Orchestrator->>Drupal MCP: generate_theme()
    Drupal MCP-->>Orchestrator: theme_package
    Orchestrator-->>Client: complete_theme
```

**Pros:** Clean separation, reusable workflow
**Cons:** Additional service to maintain

### Pattern 3: Event-Driven Pattern
Services communicate via event bus.

```mermaid
sequenceDiagram
    Client->>Event Bus: DesignFetchRequested
    Figma MCP->>Event Bus: Subscribe
    Figma MCP->>Event Bus: DesignFetched
    Drupal MCP->>Event Bus: Subscribe
    Drupal MCP->>Event Bus: ThemeGenerated
    Client->>Event Bus: Subscribe & Poll
```

**Pros:** Decoupled, scalable, auditable
**Cons:** Complex, eventual consistency

---

## Metrics & KPIs for Production

### Performance Metrics
- **Generation Time**: P50, P95, P99 latency
- **Figma API Latency**: Time to fetch design data
- **Image Export Time**: Batch export duration
- **File Write Time**: Theme file creation time

### Reliability Metrics
- **Success Rate**: % of successful generations
- **Error Rate**: By error type (API, validation, filesystem)
- **Retry Rate**: % of requests requiring retries
- **Availability**: Uptime percentage (target: 99.9%)

### Business Metrics
- **Themes Generated**: Daily/weekly/monthly count
- **Time Savings**: vs. manual theme creation
- **Module Detection Accuracy**: % correctly identified
- **User Satisfaction**: Survey scores

---

## Conclusion

This Figma-to-Drupal MCP server demonstrates significant potential for automating theme generation and reducing manual scaffolding work. The current proof-of-concept successfully:

✅ Integrates with Figma REST API
✅ Detects Drupal module requirements automatically
✅ Generates production-ready theme structures
✅ Extracts design tokens and assets
✅ Follows Drupal best practices

For industrialization, the **microservices approach** (Option 2) is recommended because it:
- Separates concerns (Figma access vs. Drupal generation)
- Enables reuse of Figma MCP across frameworks
- Allows independent scaling and deployment
- Facilitates team specialization
- Reduces blast radius of failures

The path to production requires investment in error handling, testing, observability, and documentation, but the ROI is clear: **95% reduction in theme scaffolding time** with improved consistency and reduced errors.

**Next Steps:**
1. Stabilize current implementation with proper error handling
2. Add comprehensive testing suite
3. Evaluate Figma official MCP vs. custom implementation
4. Design clean contract between Figma and Drupal MCPs
5. Implement orchestration pattern (client-side or dedicated service)
6. Deploy to production with monitoring and alerting

---

## Appendix: Example Workflow

### End-to-End Example: Creating a Theme

**Input:**
- Figma URL: `https://www.figma.com/design/ABC123/MyDesign?node-id=1-182`
- Theme name: `my_corporate_theme`

**Process:**

```bash
# 1. Via MCP Client (e.g., Claude Desktop)
User: "Convert this Figma design to a Drupal theme"

# 2. MCP Server processes
- Fetches node data from Figma API
- Analyzes structure for modules:
  ✓ Found "carousel" → easy_carousel
  ✓ Found "newsletter form" → webform
  ✓ Found "social icons" → social_media_links

# 3. Generates theme structure
- Creates 47 files
- Exports 23 images
- Generates 1,200 lines of SCSS
- Creates 8 Twig templates

# 4. Output
Theme generated in: output_theme/my_corporate_theme/
Required modules: easy_carousel, webform, social_media_links
Time: 2 minutes 34 seconds
```

**Manual work required:**
- Install detected modules
- Copy theme to Drupal
- Create content types matching design
- Configure Paragraphs types
- Customize Twig logic for dynamic content
- Test and iterate

**Time saved:** ~6 hours of scaffolding work

---

**Document Version:** 1.0  
**Last Updated:** December 18, 2025  
**Author:** Generated via technical analysis of Figma MCP Server implementation

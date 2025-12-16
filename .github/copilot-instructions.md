# Copilot Instructions for GEF Automoción Codebase

Welcome to the GEF Automoción codebase! This document provides essential guidelines for AI coding agents to be productive and aligned with the project's structure and conventions.

## Project Overview
GEF Automoción is a web-based platform for managing and showcasing vehicles. The system includes features for:
- Displaying vehicle listings with filters and sorting options.
- Managing vehicle data, including photo uploads.
- Providing a responsive and user-friendly interface.

### Key Components
1. **Frontend**:
   - HTML files (e.g., `vehiculos.php`, `index.html`) define the structure and layout.
   - CSS files in `assets/css/` provide styling.
   - JavaScript files in `assets/js/` handle interactivity.
2. **Backend**:
   - PHP scripts (e.g., `vehiculos.php`, `editar.php`) manage server-side logic and database interactions.
3. **Database**:
   - MySQL is used for storing vehicle data, including details like price, year, and photos.

### Data Flow
- User interactions (e.g., applying filters) trigger HTTP requests.
- PHP scripts process these requests, query the database, and render updated HTML.
- JavaScript enhances the user experience with dynamic elements.

## Developer Workflows
### Building and Testing
- No explicit build process; ensure all PHP, HTML, CSS, and JS files are valid.
- Use browser developer tools for debugging frontend issues.
- Check PHP error logs for backend debugging.

### Running the Project
- Open the project in a local server environment (e.g., XAMPP, WAMP).
- Access the site via `http://localhost/GEF Automoción/`.

### Debugging
- PHP: Use `error_log()` for logging errors.
- JavaScript: Use `console.log()` and browser developer tools.

## Project-Specific Conventions
1. **Styling**:
   - Follow the existing CSS patterns in `assets/css/style.css`.
   - Use Bootstrap classes for layout and responsiveness.
2. **PHP**:
   - Use helper functions like `h()` for escaping output.
   - Maintain SQL query readability with proper formatting.
3. **JavaScript**:
   - Place custom scripts in `assets/js/main.js`.
   - Use event delegation for dynamic elements.

## Integration Points
- **Database**:
  - Ensure SQL queries are secure and optimized.
  - Use prepared statements to prevent SQL injection.
- **Assets**:
  - Store images in `assets/img/`.
  - Reference assets with relative paths.

## Examples
### Collapsible Filters
The `vehiculos.php` file includes a collapsible filter section for mobile users:
```php
<div class="d-lg-none text-center mb-3">
    <button class="btn btn-outline-secondary w-100 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosColapsados" aria-expanded="false" aria-controls="filtrosColapsados">
        Mostrar/Ocultar Filtros
    </button>
</div>
```

### SQL Query Example
```php
$where = [];
if ($precioMin !== null) { $where[] = "precio >= " . (int)$precioMin; }
$whereSql = implode(' AND ', $where);
```

## Notes for AI Agents
- Prioritize responsiveness and accessibility in UI changes.
- Ensure PHP and SQL code is secure and adheres to best practices.
- Maintain consistency with existing code patterns.

For any unclear sections or additional guidance, please consult the project maintainers.

# Study Projects

A collection of PHP study projects with a static project directory hosted on Netlify.

## Netlify deployment

Netlify publishes `public/`, as configured in `netlify.toml`. No build command or dependencies are required. The landing page links to project source on GitHub.

Only files in `public/` are deployed. Keep PHP source, database exports, configuration, and private files outside this directory.

Netlify does not execute PHP. Running the PHP applications requires a PHP-capable host and the corresponding database setup; they are not live demos on this static site.

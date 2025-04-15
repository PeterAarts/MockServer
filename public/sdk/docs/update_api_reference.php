<?php
require __DIR__ . '/../../../vendor/autoload.php';
// 1. Endpoint Definitions in YAML
use Symfony\Component\Yaml\Yaml;

$yamlFile = 'ConnectingOfThings.yaml';

if (file_exists($yamlFile)) {
    $endpoints = Yaml::parseFile($yamlFile);
} else {
    $endpoints = [];
    echo "Warning: YAML file not found. Using empty endpoint definition.\n";
}

// 2. HTML Generation with Bootstrap 5.3 and YAML Viewer
function generateApiReference(array $endpoints): string
{
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $html .= '<title>API Reference</title>';
    // Include Bootstrap 5.3 CSS
    $html .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Y99vFp2yM6whb5tUibnKzL97u6o5K8i/p+6Uj9OnW7MtmkX1cKj/bBv+XUv9xj" crossorigin="anonymous">';
    // Include a YAML viewer library (e.g., js-yaml)
    $html .= '<script src="https://cdn.jsdelivr.net/npm/js-yaml@4.1.0/dist/js-yaml.min.js"></script>';
    $html .= '<style>';
    //  Custom styles (optional -  Bootstrap should handle most)
    $html .= '
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #2d3748;
        line-height: 1.7;
    }
    .container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem;
    }
    header {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }
    header h1 {
        font-size: 2.25rem;
        font-weight: 700;
        color: #1a202c;
        margin-bottom: 0.5rem;
    }
    main h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 1rem;
    }
    main h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1a202c;
        margin-bottom: 0.75rem;
    }
    main p {
        margin-bottom: 1rem;
    }
    .endpoint-section {
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .endpoint-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        cursor: pointer;
    }
    .endpoint-header h3 {
        margin-bottom: 0;
    }
    .method {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
    }
    .method-get {
        background-color: #68d391;
    }
    .method-post {
        background-color: #4a5568;
    }
    .method-put {
        background-color: #f6e05e;
        color: #1a202c;
    }
    .method-delete {
        background-color: #e53e3e;
    }
    .endpoint {
        font-size: 1rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        color: #2d3748;
    }
    .request-params {
        margin-top: 1rem;
    }
    .params-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }
    .params-table th,
    .params-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    .params-table th {
        font-weight: 600;
        color: #1a202c;
    }
    .param-name {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        color: #2d3748;
    }
    .response-example {
        background-color: #f7fafc;
        padding: 1rem;
        border-radius: 0.375rem;
        margin-top: 0.5rem;
        overflow-x: auto;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.875rem;
        line-height: 1.5rem;
    }

    /* Styles for the YAML viewer */
    .yaml-viewer {
        background-color: #f7fafc;
        padding: 1rem;
        border-radius: 0.375rem;
        margin-top: 0.5rem;
        overflow-x: auto;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.875rem;
        line-height: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    .yaml-block {
        margin-bottom: 1rem;
    }
    .group-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
        cursor: pointer;
    }
    .group-header h2 {
        margin-bottom: 0;
    }

    </style>';
    $html .= '</head><body><div class="container"><header><h1>API Reference</h1>';
    $html .= '<p class="subtitle">Detailed information about the Connecting Of Things API endpoints</p></header><main>';

    foreach ($endpoints as $groupName => $endpointsGroup) {
        $html .= '<div class="group-header">';
        $html .= '<h2>' . ucfirst($groupName) . '</h2>';
        $html .= '</div>';
        $html .= '<div class="group-endpoints">';
        foreach ($endpointsGroup as $endpointArray) { // Iterate over the array of endpoints
            foreach ($endpointArray as $endpointName => $endpointData) {
                $method = isset($endpointData['method']) ? $endpointData['method'] : 'GET';
                $path = isset($endpointData['path']) ? $endpointData['path'] : '';
                $description = isset($endpointData['description']) ? $endpointData['description'] : '';
                $parameters = isset($endpointData['parameters']) ? $endpointData['parameters'] : [];
                $response = isset($endpointData['response']) ? $endpointData['response'] : [];

                $html .= '<section id="' . $path . '" class="endpoint-section">';
                $html .= '<div class="endpoint-header" data-bs-toggle="collapse" data-bs-target="#collapse-' . $path . '" aria-expanded="false" aria-controls="collapse-' . $path . '">';
                $html .= '<span class="method method-' . strtolower($method) . '">' . $method . '</span>';
                $html .= '<h3>' . ucfirst($endpointName) . '</h3>';
                $html .= '<span class="endpoint">' . $path . '</span>';
                $html .= '</div>';
                $html .= '<div id="collapse-' . $path . '" class="collapsible-content collapse">'; // Bootstrap collapse class
                $html .= '<p>' . $description . '</p>';

                if (!empty($parameters)) {
                    $html .= '<div class="request-params"><h4>Request Parameters</h4>';
                    $html .= '<table class="params-table">
                                <thead>
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    foreach ($parameters as $param) {
                        $html .= '<tr>';
                        $html .= '<td><span class="param-name">' . (isset($param['name']) ? $param['name'] : '') . '</span></td>';
                        $html .= '<td>' . (isset($param['type']) ? $param['type'] : '') . '</td>';
                        $html .= '<td>' . (isset($param['required']) && $param['required'] ? 'Yes' : 'No') . '</td>';
                        $html .= '<td>' . (isset($param['description']) ? $param['description'] : '') . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</tbody>
                            </table>
                        </div>';
                }

                // Use the YAML viewer to display the response
                $yaml = Yaml::dump($response, 20, 2);
                $html .= '<div class="response-example"><h4>Response Example (YAML)</h4><div class="yaml-viewer"><pre id="yaml-response-' . $path . '">' . $yaml . '</pre></div></div>';
                $html .= '</div></section>';
            }
        }
        $html .= '</div>';
    }

    $html .= '</main></div>';
    // Include Bootstrap JS (required for collapse functionality)
    $html .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YpMqpRjZg5 observanceqXKp4dYVFvRz+rApFhxv17QiymEOW+qAQFC5ilKozXWqK/kvcI" crossorigin="anonymous"></script>';
    $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize YAML viewers
            const yamlElements = document.querySelectorAll(".yaml-viewer pre");
            yamlElements.forEach(el => {
                const yamlString = el.textContent;
                try {
                    const data = jsyaml.load(yamlString); //parse
                    const formattedYaml = jsyaml.dump(data, {indent: 2}); //convert back to yaml and format
                    el.innerHTML = formattedYaml; //set formatted yaml
                } catch (e) {
                    console.error("Error parsing YAML:", e);
                    el.innerHTML = yamlString;
                }
            });
        });
        </script>';
    $html .= '</body></html>';
    return $html;
}

// 3. Main execution
$apiReferenceHTML = generateApiReference($endpoints);
file_put_contents('apireference.html', $apiReferenceHTML);

echo "api-reference.html generated successfully!";
?>

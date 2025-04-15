const yamlFilePath = 'https://api.rfmsconnect.nl:9123/sdk/docs/api_endpoints.yaml';

function fetchAndParseYaml(filePath) {
    return fetch(filePath)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to fetch YAML file: ${response.status} ${response.statusText}`);
            }
            return response.text();
        })
        .then(yamlText => {
            try {
                return jsyaml.load(yamlText);
            } catch (error) {
                throw new Error(`Error parsing YAML: ${error.message}`);
            }
        });
}

function generateHtml(data) {
    let html = '';
    for (const groupName in data) {
        html += `<div class="group-header">
                    <h4>${groupName.charAt(0).toUpperCase() + groupName.slice(1)}</h4>
                </div>
                <div class="group-endpoints">`;
        const endpointsGroup = data[groupName];
        for (const endpointArray of endpointsGroup) {
            for (const endpointName in endpointArray) {
                const endpointData = endpointArray[endpointName];
                const method = endpointData.method || 'GET';
                const path = endpointData.path || '';
                const description = endpointData.description || '';
                const parameters = endpointData.parameters || [];
                const response = endpointData.response || {};

                html += `<section id="${path}" class="endpoint-section">
                            <div class="endpoint-header" data-bs-toggle="collapse" data-bs-target="#collapse-${path}" aria-expanded="false" aria-controls="collapse-${path}">
                                <span class="method method-${method.toLowerCase()}">${method}</span>
                                <h4>${endpointName.charAt(0).toUpperCase() + endpointName.slice(1)}</h4>
                                <span class="endpoint">${path}</span>
                            </div>
                            <div id="collapse-${path}" class="collapsible-content collapse">
                                <p>${description}</p>`;

                if (parameters.length > 0) {
                    html += `<div class="request-params">
                                <h4>Request Parameters</h4>
                                <table class="params-table">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                    for (const param of parameters) {
                        html += `<tr>
                                    <td><span class="param-name">${param.name || ''}</span></td>
                                    <td>${param.type || ''}</td>
                                    <td>${param.required ? 'Yes' : 'No'}</td>
                                    <td>${param.description || ''}</td>
                                </tr>`;
                    }
                    html += `</tbody>
                                </table>
                            </div>`;
                }

                const yamlResponse = jsyaml.dump(response, { indent: 2 });
                html += `<div class="response-example">
                            <h4>Response Example (YAML)</h4>
                            <div class="yaml-viewer"><pre>${yamlResponse}</pre></div>
                        </div>
                    </div>
                </section>`;
            }
        }
        html += '</div>'; // group-endpoints
    }
    return html;
}

function renderHtml(html) {
    const apiContent = document.getElementById('api-content');
    apiContent.innerHTML = html;
    // Initialize Bootstrap collapse functionality
    const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
    collapseElements.forEach(element => {
        new bootstrap.Collapse(element);
    });
}

function loadAndDisplayApiReference() {
    fetchAndParseYaml(yamlFilePath)
        .then(data => {
            const html = generateHtml(data);
            renderHtml(html);
        })
        .catch(error => {
            const apiContent = document.getElementById('api-content');
            apiContent.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            console.error(error);
        });
}

document.addEventListener('DOMContentLoaded', loadAndDisplayApiReference);
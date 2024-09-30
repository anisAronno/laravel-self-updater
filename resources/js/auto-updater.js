// auto-updater.js
document.addEventListener('DOMContentLoaded', () => {
    const elements = {
        autoUpdaterContent: document.querySelector('.auto-updater-content'),
        outputContainer: document.getElementById('outputContainer'),
        refreshMessage: document.getElementById('refreshMessage')
    };

    const config = {
        checkUrl: '', // This will be set dynamically
        updateUrl: '', // This will be set dynamically
        csrfToken: '' // This will be set dynamically
    };

    const utils = {
        showMessage: (message, isError = false, targetElement) => {            
            if (targetElement) {
                targetElement.style.display = 'block';
                targetElement.style.color = isError ? 'red' : 'green';
                targetElement.textContent = message;
            }            
        },
        clearMessage: (messageElement) => {
            if (messageElement) {
                messageElement.style.display = 'none';
                messageElement.textContent = '';
            }
        },
        toggleSpinner: (element, isSpinning) => {
            if (element) {
                element.style.color = isSpinning ? 'green' : 'red';
                element.classList.toggle('spin', isSpinning);
            }
        },
        handleResponse: async (response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return await response.json();
        }
    };

    const updateUI = (data) => {
        elements.autoUpdaterContent.innerHTML = ''; // Clear existing content

        if (data.error) {
            elements.autoUpdaterContent.innerHTML = `<div class="error-message">${data.error}</div>`;
            return;
        }

        const versionInfo = `
        <p>Current Version: <span id="currentVersion" class="version-tag">${data.currentVersion || 'Unknown'}</span></p>
        <p>Latest Version: <span id="latestVersion" class="version-tag">${data.latestVersion || 'Unknown'}</span>
            <span id="refreshIcon" class="refresh-icon" style="cursor: pointer;">&#x21bb;</span>
        </p>`;
        elements.autoUpdaterContent.innerHTML += versionInfo;

        if (data.hasUpdate) {
            const updateButton = `<button id="updateButton" class="update-button">Update Now</button>`;
            elements.autoUpdaterContent.innerHTML += updateButton;

            if (data.changelog) {
                const changelogSection = `
                <button id="toggleChangelog" class="changelog-button">Show Changelog</button>
                <div id="changelogContainer" class="changelog-container" style="display: none;">
                    <pre id="changelog" class="changelog-content">${data.changelog}</pre>
                </div>
            `;
                elements.autoUpdaterContent.innerHTML += changelogSection;
            }
        } else if (data.current_version && data.latest_version) {
            elements.autoUpdaterContent.innerHTML +=
                `<p class="up-to-date">Your application is up to date!</p>`;
        } else {
            elements.autoUpdaterContent.innerHTML +=
                `<p class="warning-message">Unable to determine update status. Please try refreshing.</p>`;
        }

        elements.autoUpdaterContent.innerHTML += `
        <div id="outputContainer" class="output-container" style="display: none;">
            <pre id="output" class="output-content"></pre>
        </div>
        <p id="refreshMessage" class="refresh-message" style="display: none;"></p>
    `;

        // Re-attach event listeners
        attachEventListeners();
    };

    const attachEventListeners = () => {
        const refreshIcon = document.getElementById('refreshIcon');
        if (refreshIcon) {
            refreshIcon.addEventListener('click', refreshData);
        }

        const updateButton = document.getElementById('updateButton');
        if (updateButton) {
            updateButton.addEventListener('click', initiateUpdate);
        }

        const changelogButton = document.getElementById('toggleChangelog');
        const changelogContainer = document.getElementById('changelogContainer');
        if (changelogButton && changelogContainer) {
            changelogButton.addEventListener('click', () => {
                const isHidden = changelogContainer.style.display === 'none';
                changelogContainer.style.display = isHidden ? 'block' : 'none';
                changelogButton.textContent = isHidden ? 'Hide Changelog' : 'Show Changelog';
            });
        }
    };

    const refreshData = async () => {
        const refreshIcon = document.getElementById('refreshIcon');

        utils.clearMessage(elements.refreshMessage);
        utils.toggleSpinner(refreshIcon, true);

        try {
            const response = await fetch(config.checkUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            });

            const data = await utils.handleResponse(response);

            if (data.success) {
                updateUI(data);
                displayRefreshMessage(data.message || 'Refresh completed successfully!');
            } else {
                throw new Error(data.message || 'Error checking for update');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showMessage('An error occurred: ' + error.message, true, elements.refreshMessage);
        } finally {
            utils.toggleSpinner(refreshIcon, false);
            
            // Add a timeout to clear the success message after 3 seconds
            setTimeout(() => {
                utils.clearMessage(elements.refreshMessage);
            }, 3000);
        }
    };

    const initiateUpdate = async () => {
        const updateButton = document.getElementById('updateButton');
        const outputContainer = document.getElementById('outputContainer');
        const output = document.getElementById('output');

        utils.clearMessage(output);
        utils.toggleSpinner(updateButton, true);
        outputContainer.style.display = 'block';

        try {
            const response = await fetch(config.updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                }
            });

            const data = await utils.handleResponse(response);

            if (data.success) {
                utils.showMessage(data.message || 'Update completed successfully!', false, output);
                refreshData();
            } else {
                throw new Error(data.message || 'Error initiating update');
            }
        } catch (error) {
            console.error('Error:', error);
            utils.showMessage('An error occurred: ' + error.message, true, output);
        } finally {
            utils.toggleSpinner(updateButton, false);
        }
    };

    const displayRefreshMessage = function (message) {
        requestAnimationFrame(() => {
            const refreshMessage = document.getElementById('refreshMessage');
            if (refreshMessage) {
                utils.showMessage(message, false, refreshMessage);
                setTimeout(() => {
                    utils.clearMessage(refreshMessage);
                }, 3000);
            }
        });
    };
    
    // Initial attachment of event listeners
    attachEventListeners();

    // Expose necessary functions to the global scope
    window.autoUpdater = {
        setConfig: (newConfig) => {
            Object.assign(config, newConfig);
        },
        refreshData: refreshData,
        initiateUpdate: initiateUpdate
    };
});
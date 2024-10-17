// self-updater.js
document.addEventListener('DOMContentLoaded', function() {
    var config = {
        checkUrl: '',
        updateUrl: '',
        csrfToken: ''
    };

    function updateUI(data) {
        var content = document.querySelector('.self-updater-content');
        if (!content) return;

        if (data.error) {
            content.innerHTML = `<div class="error-message">${data.error}</div>`;
            return;
        }

        content.innerHTML = `
            <p>Current Version: <span id="currentVersion" class="version-tag">${data.currentVersion || 'Unknown'}</span></p>
            <p>Latest Version: <span id="latestVersion" class="version-tag">${data.latestVersion || 'Unknown'}</span>
                <span id="refreshIcon" class="refresh-icon" style="cursor: pointer;">&#x21bb;</span>
            </p>
        `;

        if (data.hasUpdate) {
            content.innerHTML += `
                <p>Release Date: <span id="releaseDate" class="version-tag">${data.releaseDate || 'Unknown'}</span></p>
                <button id="updateButton" class="update-button">Update Now</button>
                <button id="toggleChangelog" class="changelog-button">Show Changelog</button>
                <div id="changelogContainer" class="changelog-container" style="display: none;">
                    <pre id="changelog" class="changelog-content">${data.changelog || 'No changelog available'}</pre>
                </div>
            `;
        } else if (data.currentVersion && data.latestVersion) {
            content.innerHTML += '<p class="up-to-date">Your application is up to date!</p>';
        } else {
            content.innerHTML += '<p class="warning-message">Unable to determine update status. Please try refreshing.</p>';
        }

        attachEventListeners();
    }

    function attachEventListeners() {
        var refreshIcon = document.getElementById('refreshIcon');
        if (refreshIcon) {
            refreshIcon.addEventListener('click', checkForUpdates);
        }

        var updateButton = document.getElementById('updateButton');
        if (updateButton) {
            updateButton.addEventListener('click', initiateUpdate);
        }

        var changelogButton = document.getElementById('toggleChangelog');
        var changelogContainer = document.getElementById('changelogContainer');
        if (changelogButton && changelogContainer) {
            changelogButton.addEventListener('click', function() {
                var isHidden = changelogContainer.style.display === 'none';
                changelogContainer.style.display = isHidden ? 'block' : 'none';
                changelogButton.textContent = isHidden ? 'Hide Changelog' : 'Show Changelog';
            });
        }
    }

    function showMessage(message, isError = false) {
        var refreshMessage = document.getElementById('refreshMessage');
        if (refreshMessage) {
            refreshMessage.innerHTML = message.replace(/\n/g, '<br>');
            refreshMessage.style.color = isError ? 'red' : 'green';
            refreshMessage.style.display = 'block';
            refreshMessage.style.whiteSpace = 'pre-wrap';
            refreshMessage.style.wordBreak = 'break-word';
            setTimeout(() => {
                refreshMessage.style.display = 'none';
            }, 5000);  // Increased to 5 seconds for longer messages
        }
    }

    function checkForUpdates() {
        var refreshIcon = document.getElementById('refreshIcon');
        if (refreshIcon) refreshIcon.classList.add('spin');

        fetch(config.checkUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            updateUI(data);
            showMessage('Refresh completed successfully!');
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Failed to check for updates. Please try again later.', true);
        })
        .finally(() => {
            if (refreshIcon) refreshIcon.classList.remove('spin');
        });
    }

    function initiateUpdate() {
        var updateButton = document.getElementById('updateButton');
        var updateSpinner = document.getElementById('updateSpinner');

        if (updateButton) updateButton.disabled = true;
        if (updateSpinner) updateSpinner.style.display = 'flex';

        fetch(config.updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            showMessage(data.message || 'Update completed successfully!');
            setTimeout(() => {
                checkForUpdates();
            }
            , 3000);
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Failed to update. Please try again later.', true);
        })
        .finally(() => {
            if (updateButton) updateButton.disabled = false;
            if (updateSpinner) updateSpinner.style.display = 'none';
        });
    }

    function ensureSpinnerExists() {
        var card = document.querySelector('.self-updater-card');
        if (card && !document.getElementById('updateSpinner')) {
            card.insertAdjacentHTML('beforeend', `
                <div id="updateSpinner" class="update-spinner" style="display: none;">
                    <div class="spinner"></div>
                    <p>Updating... Please wait.</p>
                </div>
            `);
        }
    }

    window.selfUpdater = {
        setConfig: function(newConfig) {
            config = Object.assign(config, newConfig);
            attachEventListeners();
            ensureSpinnerExists();
        }
    };
});
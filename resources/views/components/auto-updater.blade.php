<div id="autoUpdater" class="auto-updater-card">
    <div class="auto-updater-header">
        <h2>Auto Updater</h2>
    </div>

    <div class="auto-updater-content">
        @if ($error)
            <div class="error-message">{{ $error }}</div>
        @else
            <p>Current Version: <span id="currentVersion" class="version-tag">{{ $currentVersion ?? 'Unknown' }}</span>
            </p>
            <p>Latest Version: <span id="latestVersion" class="version-tag">{{ $latestVersion ?? 'Unknown' }}</span>
                <span id="refreshIcon" class="refresh-icon" style="cursor: pointer;">&#x21bb;</span>
            </p>

            @if ($hasUpdate)
                <p>Release Date: <span id="releaseDate" class="version-tag">{{ $releaseDate ?? 'Unknown' }}</span></p>
                <button id="updateButton" class="update-button">Update Now</button>

                <button id="toggleChangelog" class="changelog-button">Show Changelog</button>
                <div id="changelogContainer" class="changelog-container" style="display: none;">
                    @if ($changelog)
                        <pre id="changelog" class="changelog-content">{{ $changelog }}</pre>
                    @else
                        <pre class="changelog-content"> No changelog available</pre>
                    @endif
                </div>
            @elseif ($currentVersion && $latestVersion)
                <p class="up-to-date">Your application is up to date!</p>
            @else
                <p class="warning-message">Unable to determine update status. Please try refreshing.</p>
            @endif
        @endif
    </div>
    <p id="refreshMessage" class="refresh-message" style="display: none;"></p>
</div>

@once
    <link rel="stylesheet" href="{{ asset('vendor/auto-updater/css/auto-updater.css') }}">
    <script src="{{ asset('vendor/auto-updater/js/auto-updater.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.autoUpdater.setConfig({
                checkUrl: '{{ route('auto_updater.check') }}',
                updateUrl: '{{ route('auto_updater.update') }}',
                csrfToken: '{{ csrf_token() }}'
            });
        });
    </script>
@endonce

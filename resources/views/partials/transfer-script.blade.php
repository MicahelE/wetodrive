{{-- Transfer form handling with live progress polling. Shared by the current
     homepage and the v2 test homepage, which reuse the same element ids. --}}
        // Transfer Form Handling with Progress
        @auth
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[DEBUG] DOMContentLoaded - Initializing transfer form handler');

            const transferForm = document.getElementById('transferForm');
            console.log('[DEBUG] Transfer form element:', transferForm);

            if (transferForm) {
                console.log('[DEBUG] Adding submit event listener to form');

                transferForm.addEventListener('submit', function(e) {
                    console.log('[DEBUG] ============ FORM SUBMISSION START ============');
                    console.log('[DEBUG] Form submit event triggered at:', new Date().toISOString());
                    console.log('[DEBUG] Event type:', e.type);
                    console.log('[DEBUG] Event target:', e.target);
                    console.log('[DEBUG] Default prevented before:', e.defaultPrevented);

                    e.preventDefault();
                    e.stopPropagation();

                    console.log('[DEBUG] preventDefault() and stopPropagation() called');
                    console.log('[DEBUG] Default prevented after:', e.defaultPrevented);

                    const formData = new FormData(this);
                    const transferUrl = document.getElementById('wetransfer_url').value;

                    console.log('[DEBUG] Form data prepared:');
                    console.log('[DEBUG] - WeTransfer URL:', transferUrl);
                    console.log('[DEBUG] - FormData entries:');
                    for (let pair of formData.entries()) {
                        console.log('[DEBUG]   -', pair[0], ':', pair[1]);
                    }

                    // Track analytics
                    if (typeof trackFileTransfer === 'function') {
                        console.log('[DEBUG] Tracking analytics');
                        trackFileTransfer(transferUrl);
                    }

                    // Hide form, show progress
                    console.log('[DEBUG] Switching UI to progress view');
                    startedHere = true; // the counts on this page predate this transfer
                    document.getElementById('transferFormContainer').style.display = 'none';
                    document.getElementById('progressContainer').style.display = 'block';

                    // Send Ajax request
                    const fetchUrl = '{{ route("transfer") }}';
                    const fetchOptions = {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    };

                    console.log('[DEBUG] ============ STARTING AJAX REQUEST ============');
                    console.log('[DEBUG] Request started at:', new Date().toISOString());
                    console.log('[DEBUG] - URL:', fetchUrl);
                    console.log('[DEBUG] - Method:', fetchOptions.method);
                    console.log('[DEBUG] - Headers:', JSON.stringify(fetchOptions.headers, null, 2));
                    console.log('[DEBUG] - Body is FormData with entries:', Array.from(formData.entries()));
                    const startTime = performance.now();

                    fetch(fetchUrl, fetchOptions)
                    .then(response => {
                        const responseTime = performance.now() - startTime;
                        console.log('[DEBUG] ============ RESPONSE RECEIVED ============');
                        console.log('[DEBUG] Response received at:', new Date().toISOString());
                        console.log('[DEBUG] Response time:', responseTime.toFixed(2), 'ms');
                        console.log('[DEBUG] - Status:', response.status);
                        console.log('[DEBUG] - Status Text:', response.statusText);
                        console.log('[DEBUG] - OK:', response.ok);
                        console.log('[DEBUG] - Type:', response.type);
                        console.log('[DEBUG] - URL:', response.url);
                        console.log('[DEBUG] Response headers:');
                        for (let [key, value] of response.headers.entries()) {
                            console.log('[DEBUG]   -', key + ':', value);
                        }

                        // Clone response to read it twice if needed
                        const clonedResponse = response.clone();

                        if (!response.ok) {
                            console.error('[DEBUG] Response not OK, attempting to parse error');
                            return clonedResponse.text().then(text => {
                                console.error('[DEBUG] Error response text:', text);
                                try {
                                    const err = JSON.parse(text);
                                    console.error('[DEBUG] Parsed error:', err);
                                    return Promise.reject(err);
                                } catch (e) {
                                    console.error('[DEBUG] Could not parse error as JSON:', e);
                                    return Promise.reject({error: text});
                                }
                            });
                        }

                        return response.text().then(text => {
                            console.log('[DEBUG] Success response text:', text);
                            try {
                                const data = JSON.parse(text);
                                console.log('[DEBUG] Parsed response data:', data);
                                return data;
                            } catch (e) {
                                console.error('[DEBUG] Could not parse response as JSON:', e);
                                throw new Error('Invalid JSON response: ' + text);
                            }
                        });
                    })
                    .then(data => {
                        console.log('[DEBUG] Processing response:', data);
                        if (data.success) {
                            console.log('[DEBUG] ============ TRANSFER INITIATED ============');
                            console.log('[DEBUG] - Transfer ID:', data.transfer_id);
                            console.log('[DEBUG] - Filename:', data.filename);
                            console.log('[DEBUG] - Size:', data.size, 'bytes (' + formatBytes(data.size) + ')');
                            console.log('[DEBUG] - Status:', data.status);

                            // Update UI with file info
                            if (data.filename) {
                                document.getElementById('progressFilename').textContent = data.filename;
                            }
                            if (data.size) {
                                document.getElementById('totalSize').textContent = formatBytes(data.size);
                            }

                            if (data.status === 'processing') {
                                // Transfer started in background - connect to SSE for progress
                                console.log('[DEBUG] Transfer processing in background, starting SSE monitoring');
                                document.getElementById('progressStatus').textContent = 'Starting transfer...';
                                startProgressMonitoring(data.transfer_id);
                            } else if (data.google_drive_id) {
                                // Immediate success (shouldn't happen with new async flow, but handle it)
                                console.log('[DEBUG] Immediate success - Google Drive ID:', data.google_drive_id);
                                document.getElementById('bytesTransferred').textContent = formatBytes(data.size);
                                document.getElementById('progressBar').style.width = '100%';
                                document.getElementById('progressPercent').textContent = '100%';
                                document.getElementById('progressStatus').textContent = 'Transfer Complete';

                                setTimeout(() => {
                                    alert('File successfully transferred to Google Drive!');
                                    resetTransferForm();
                                }, 1000);
                            }
                        } else {
                            console.error('[DEBUG] Response indicates failure:', data);
                            throw new Error(data.error || 'Transfer failed');
                        }
                    })
                    .catch(error => {
                        const errorTime = performance.now() - startTime;
                        console.error('[DEBUG] ============ TRANSFER ERROR ============');
                        console.error('[DEBUG] Error occurred at:', new Date().toISOString());
                        console.error('[DEBUG] Time until error:', (errorTime / 1000).toFixed(2), 'seconds');
                        console.error('[DEBUG] Error type:', error.constructor.name);
                        console.error('[DEBUG] Error object:', error);
                        console.error('[DEBUG] Error message:', error.message || error.error || 'Unknown error');
                        console.error('[DEBUG] Error stack:', error.stack);
                        console.error('[DEBUG] Full error details:', JSON.stringify(error, null, 2));

                        // Show error message
                        document.getElementById('progressStatus').textContent = 'Transfer Failed';
                        document.getElementById('statusMessage').style.display = 'none';
                        document.getElementById('completionMessage').style.display = 'block';

                        // Handle different error types with appropriate UX
                        if (error.is_wetransfer_error) {
                            // WeTransfer expired/invalid link error - blue info box
                            let suggestionsHtml = error.suggestions
                                ? '<ul style="text-align: left; margin: 10px 0; padding-left: 20px;">' +
                                  error.suggestions.map(s => `<li style="margin: 5px 0;">${s}</li>`).join('') +
                                  '</ul>'
                                : '';

                            document.getElementById('completionMessage').innerHTML = `
                                <div style="background: #e7f3ff; border: 1px solid #b8daff; color: #004085; padding: 15px; border-radius: 8px;">
                                    <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">Link Unavailable</div>
                                    <div>${error.error || 'This WeTransfer link is no longer available.'}</div>
                                    ${suggestionsHtml}
                                    <button onclick="resetTransferForm()" style="margin-top: 10px; background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        Try Different Link
                                    </button>
                                </div>
                            `;
                        } else if (error.is_limit_error && error.upgrade_url) {
                            // File size limit error - yellow warning box naming the plan the file needs.
                            const ctaLabel = error.recommended_plan_name
                                ? `See ${error.recommended_plan_name} plan`
                                : 'See plans';
                            document.getElementById('completionMessage').innerHTML = `
                                <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 8px;">
                                    <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">File Too Large</div>
                                    <div style="margin-bottom: 15px;">${error.error || 'File exceeds your plan limit.'}</div>
                                    <a href="${error.upgrade_url}" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 10px;">
                                        ${ctaLabel}
                                    </a>
                                    <button onclick="resetTransferForm()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        Try Different File
                                    </button>
                                </div>
                            `;
                        } else {
                            // Generic error - red error box (original behavior)
                            document.getElementById('completionMessage').innerHTML = `
                                <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px;">
                                    <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">Transfer Failed</div>
                                    <div>${error.error || error.message || 'An error occurred while starting the transfer.'}</div>
                                    <button onclick="resetTransferForm()" style="margin-top: 15px; background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        Try Again
                                    </button>
                                </div>
                            `;
                        }
                    });

                    console.log('[DEBUG] Returning false to prevent default submission');
                    return false; // Extra prevention of form submission
                });
            } else {
                console.error('[DEBUG] Transfer form not found!');
            }

            // A transfer outlives the tab that started it, so if the server says
            // one is still running (or finished within the last 15 minutes), drop
            // straight back into the progress view instead of an empty form.
            const resumeId = document.getElementById('progressContainer')?.dataset.resume;
            if (resumeId && localStorage.getItem('wtd_seen_transfer') !== resumeId) {
                console.log('[DEBUG] Reattaching to in-flight transfer:', resumeId);
                document.getElementById('transferFormContainer').style.display = 'none';
                document.getElementById('progressContainer').style.display = 'block';
                startProgressMonitoring(resumeId);
            }
        });

        // Whatever transfer this tab is currently watching, whether it started it
        // or reattached to it. resetTransferForm() needs it to mark the result seen.
        let currentTransferId = null;

        // False when we reattached to a transfer already in flight. The counts on
        // the page were rendered by the server, so whether they already include
        // this transfer depends on which of the two happened first.
        let startedHere = false;

        function startProgressMonitoring(transferId) {
            currentTransferId = transferId;
            let reconnectAttempts = 0;
            const maxReconnectAttempts = 10;
            const reconnectDelay = 2000; // 2 seconds
            let isComplete = false;

            function connect() {
                const url = '{{ route("transfer.progress") }}?transfer_id=' + transferId;
                console.log('[DEBUG] SSE connecting to:', url);
                const eventSource = new EventSource(url);

                eventSource.onopen = function() {
                    console.log('[DEBUG] SSE connection opened');
                    reconnectAttempts = 0; // Reset on successful connection
                };

                eventSource.onmessage = function(event) {
                    try {
                        const data = JSON.parse(event.data);
                        updateProgress(data);
                    } catch (e) {
                        console.error('Error parsing progress data:', e);
                    }
                };

                eventSource.addEventListener('complete', function(event) {
                    console.log('[DEBUG] SSE complete event received:', event.data);
                    isComplete = true;
                    eventSource.close();

                    try {
                        const data = JSON.parse(event.data);

                        if (data.status === 'completed' && data.success) {
                            console.log('[DEBUG] Transfer completed successfully via SSE');
                            console.log('[DEBUG] Google Drive ID:', data.google_drive_id);

                            // Update UI to show completion
                            document.getElementById('progressBar').style.width = '100%';
                            document.getElementById('progressPercent').textContent = '100%';
                            document.getElementById('progressStatus').textContent = 'Transfer Complete!';
                            document.getElementById('statusMessage').style.display = 'none';
                            document.getElementById('completionMessage').style.display = 'block';

                            // Build success message with Google Drive link
                            let successHtml = `
                                <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px;">
                                    <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">Transfer Successful!</div>
                                    <div style="margin-bottom: 10px;">Your file has been transferred to Google Drive.</div>`;

                            if (data.google_drive_id) {
                                successHtml += `
                                    <a href="https://drive.google.com/file/d/${data.google_drive_id}/view" target="_blank"
                                       style="display: inline-block; background: #4285f4; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">
                                        View in Google Drive
                                    </a><br>`;
                            }

                            successHtml += `
                                    <button onclick="resetTransferForm()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        Transfer Another File
                                    </button>
                                </div>`;

                            if (data.show_upgrade_prompt) {
                                successHtml += `
                                    <div style="background: #e7f3ff; border: 1px solid #b8daff; color: #004085; padding: 15px; border-radius: 8px; margin-top: 12px;">
                                        <div style="font-weight: 600; margin-bottom: 6px;">Need bigger transfers?</div>
                                        <div style="margin-bottom: 12px;">Upgrade to Pro for 25GB file transfers and more.</div>
                                        <a href="{{ route('subscription.pricing') }}"
                                           style="display: inline-block; background: #4285f4; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                                            Upgrade Plan
                                        </a>
                                    </div>`;
                            }

                            document.getElementById('completionMessage').innerHTML = successHtml;

                            // Nudge the counts, but only for a transfer this page
                            // watched from the start. On a reattach the server has
                            // already counted it, and adjusting again showed people
                            // one fewer transfer than they actually had left.
                            if (startedHere) {
                                const transfersRemainingEl = document.querySelector('[data-transfers-remaining]');
                                if (transfersRemainingEl) {
                                    const current = parseInt(transfersRemainingEl.textContent);
                                    if (!isNaN(current) && current > 0) {
                                        transfersRemainingEl.textContent = current - 1;
                                    }
                                }

                                const totalTransfersEl = document.querySelector('[data-total-transfers]');
                                if (totalTransfersEl) {
                                    const current = parseInt(totalTransfersEl.textContent);
                                    if (!isNaN(current)) {
                                        totalTransfersEl.textContent = current + 1;
                                    }
                                }
                            }

                        } else if (data.status === 'failed') {
                            console.error('[DEBUG] Transfer failed via SSE:', data.error, 'needs_reconnect:', data.needs_reconnect);

                            document.getElementById('statusMessage').style.display = 'none';
                            document.getElementById('completionMessage').style.display = 'block';

                            if (data.needs_reconnect) {
                                document.getElementById('progressStatus').textContent = 'Reconnection Required';
                                document.getElementById('completionMessage').innerHTML = `
                                    <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 8px;">
                                        <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">Reconnection Required</div>
                                        <div style="margin-bottom: 10px;">${data.error || 'Your Google Drive connection needs to be refreshed.'}</div>
                                        <form id="reconnect-form" action="{{ route('auth.disconnect') }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" style="background: #4285f4; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                                Reconnect to Google Drive
                                            </button>
                                        </form>
                                    </div>`;
                            } else {
                                document.getElementById('progressStatus').textContent = 'Transfer Failed';
                                document.getElementById('completionMessage').innerHTML = `
                                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px;">
                                        <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">Transfer Failed</div>
                                        <div style="margin-bottom: 10px;">${data.error || 'An error occurred during the transfer.'}</div>
                                        <button onclick="resetTransferForm()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                            Try Again
                                        </button>
                                    </div>`;
                            }
                        }
                    } catch (e) {
                        console.error('[DEBUG] Error parsing complete event data:', e);
                    }
                });

                eventSource.onerror = function(error) {
                    console.error('[DEBUG] SSE connection error:', error);
                    eventSource.close();

                    // Don't reconnect if transfer is already complete
                    if (isComplete) {
                        return;
                    }

                    // Attempt reconnection
                    if (reconnectAttempts < maxReconnectAttempts) {
                        reconnectAttempts++;
                        console.log('[DEBUG] SSE reconnecting (' + reconnectAttempts + '/' + maxReconnectAttempts + ') in ' + (reconnectDelay/1000) + 's...');
                        document.getElementById('progressStatus').textContent = 'Reconnecting... (' + reconnectAttempts + '/' + maxReconnectAttempts + ')';

                        setTimeout(function() {
                            connect();
                        }, reconnectDelay);
                    } else {
                        console.error('[DEBUG] Max SSE reconnect attempts reached');
                        document.getElementById('progressStatus').textContent = 'Connection lost';
                        document.getElementById('statusMessage').style.display = 'none';
                        document.getElementById('completionMessage').style.display = 'block';
                        document.getElementById('completionMessage').innerHTML = `
                            <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">Connection Lost</div>
                                <div style="margin-bottom: 10px;">Lost connection to the server. Your transfer may still be completing in the background. Check your Google Drive in a few minutes.</div>
                                <button onclick="resetTransferForm()" style="background: #ffc107; color: #212529; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                    Start New Transfer
                                </button>
                            </div>`;
                    }
                };
            }

            // Start initial connection
            connect();

            // Alternative implementation using fetch (if EventSource doesn't work)
            /*
            fetch(url, {
                headers: {
                    'Accept': 'text/event-stream',
                }
            }).then(response => {
                if (!response.ok) return;

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                function readStream() {
                    reader.read().then(({done, value}) => {
                        if (done) return;

                        const chunk = decoder.decode(value);
                        const lines = chunk.split('\n');

                        lines.forEach(line => {
                            if (line.startsWith('data: ')) {
                                try {
                                    const data = JSON.parse(line.substring(6));
                                    updateProgress(data);
                                } catch (e) {
                                    console.error('Error parsing SSE data:', e);
                                }
                            }
                        });

                        readStream();
                    });
                }

                readStream();
            }).catch(error => {
                console.error('Error connecting to progress stream:', error);
            });
            */
        }

        function updateProgress(data) {
            // Update progress bar
            const percentage = data.percentage || 0;
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressPercent').textContent = Math.round(percentage) + '%';

            // Update bytes transferred
            const bytesTransferred = formatBytes(data.bytesTransferred || 0);
            const totalBytes = formatBytes(data.totalBytes || 0);
            document.getElementById('bytesTransferred').textContent = bytesTransferred;
            document.getElementById('totalSize').textContent = totalBytes;

            // Update filename
            if (data.filename) {
                document.getElementById('progressFilename').textContent = data.filename;
            }

            // Update status. The server reports the two phases separately, so
            // without these the heading sits on whatever it last said while the
            // bar climbs, which reads as a stuck transfer.
            if (data.status === 'downloading') {
                document.getElementById('progressStatus').textContent = 'Downloading from WeTransfer...';
            } else if (data.status === 'uploading') {
                document.getElementById('progressStatus').textContent = 'Uploading to Google Drive...';
            } else if (data.status === 'transferring') {
                document.getElementById('progressStatus').textContent = 'Transferring to Google Drive...';
                document.getElementById('statusMessage').innerHTML = '<span>⏳ Transfer in progress... Please wait.</span>';
            } else if (data.status === 'completed') {
                document.getElementById('progressStatus').textContent = 'Transfer Complete!';
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressPercent').textContent = '100%';
                document.getElementById('statusMessage').style.display = 'none';
                document.getElementById('completionMessage').style.display = 'block';

                // The counters are deliberately NOT touched here. The stream sends
                // a 'completed' message and then a 'complete' event for the same
                // transfer, so doing it in both places counted every transfer twice
                // and could show "0 left" to someone who still had one.

                document.getElementById('completionMessage').innerHTML = `
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px;">
                        <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">✅ Transfer Successful!</div>
                        <div style="margin-bottom: 10px;">Your file has been transferred to Google Drive.</div>
                        <button onclick="resetTransferForm()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            Transfer Another File
                        </button>
                    </div>
                `;
            } else if (data.status === 'failed') {
                document.getElementById('progressStatus').textContent = 'Transfer Failed';
                document.getElementById('statusMessage').style.display = 'none';
                document.getElementById('completionMessage').style.display = 'block';
                document.getElementById('completionMessage').innerHTML = `
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px;">
                        <div style="font-size: 1.2rem; font-weight: 600; margin-bottom: 10px;">❌ Transfer Failed</div>
                        <div>There was an error transferring your file. Please try again.</div>
                        <button onclick="resetTransferForm()" style="margin-top: 15px; background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            Try Again
                        </button>
                    </div>
                `;
            }
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function resetTransferForm() {
            // Remember that this result has been seen, so reloading the page does
            // not put the same success box back up for the rest of its 15 minutes.
            if (currentTransferId) {
                localStorage.setItem('wtd_seen_transfer', currentTransferId);
            }

            // Reset form and UI
            document.getElementById('transferForm').reset();
            document.getElementById('transferFormContainer').style.display = 'block';
            document.getElementById('progressContainer').style.display = 'none';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressPercent').textContent = '0%';
            document.getElementById('bytesTransferred').textContent = '0 MB';
            document.getElementById('totalSize').textContent = '0 MB';
            document.getElementById('progressFilename').textContent = '';
            document.getElementById('progressStatus').textContent = 'Initializing transfer...';
            document.getElementById('statusMessage').style.display = 'block';
            document.getElementById('statusMessage').innerHTML = '<span>⏳ Transfer in progress... Please wait.</span>';
            document.getElementById('completionMessage').style.display = 'none';
        }
        @endauth

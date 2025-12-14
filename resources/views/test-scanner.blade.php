<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Test Scanner</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .btn {
            width: 100%;
            padding: 18px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-scan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 15px;
        }
        
        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        
        .btn-scan:active {
            transform: translateY(0);
        }
        
        .btn-random {
            background: #f0f0f0;
            color: #666;
            margin-bottom: 20px;
        }
        
        .btn-random:hover {
            background: #e0e0e0;
        }
        
        #result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.8;
            display: none;
        }
        
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
        }
        
        .loading {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-box strong {
            color: #1976D2;
        }
        
        .scan-log {
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
        
        .scan-item {
            padding: 10px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            font-size: 13px;
        }
        
        .scan-item strong {
            color: #667eea;
        }
        
        code {
            background: #f4f4f4;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏷️ RFID Test Scanner</h1>
        <p class="subtitle">Test scanning without the Android app</p>
        
        <div class="info-box">
            <strong>⚠️ IMPORTANT:</strong><br>
            This page is for testing the API with REAL tag EPCs only.<br>
            <br>
            <strong>How to use:</strong><br>
            1. Scan a physical RFID tag with your Zebra device<br>
            2. Copy the EPC code from the scan log<br>
            3. Paste it here to test the API endpoint<br>
            <br>
            <strong>DO NOT</strong> generate fake tags - use real scanned EPCs only!
        </div>
        
        <div class="input-group">
            <label>EPC Code (from your physical RFID tag)</label>
            <input type="text" id="epcCode" placeholder="Enter the EPC from your physical tag" maxlength="24">
            <small style="color: #666; display: block; margin-top: 5px;">
                ⚠️ Enter ONLY the EPC code from a real RFID tag you've scanned
            </small>
        </div>
        
        <div class="input-group">
            <label>Operation Type</label>
            <select id="operationType">
                <option value="receiving">📦 RECEIVING (accepts any tag)</option>
                <option value="picking">📋 PICKING (requires registered tag)</option>
                <option value="shipping">🚚 SHIPPING (requires registered tag)</option>
                <option value="count">📊 COUNT (requires registered tag)</option>
            </select>
        </div>
        
        <button class="btn btn-scan" onclick="sendScan()">
            📡 Send Scan to API
        </button>
        
        <div id="result"></div>
        
        <div class="scan-log" id="scanLog" style="display:none;">
            <strong>Scan History:</strong>
            <div id="scanHistory"></div>
        </div>
    </div>

    <script>
        const API_URL = window.location.origin + '/api';
        let scanCount = 0;

        function generateRandomEpc() {
            const hex = '0123456789ABCDEF';
            let epc = '';
            
            // Generate completely random 24-character hex string
            for (let i = 0; i < 24; i++) {
                epc += hex[Math.floor(Math.random() * 16)];
            }
            
            document.getElementById('epcCode').value = epc;
            showResult('Generated: ' + epc, 'success');
        }

        async function sendScan() {
            const epcCode = document.getElementById('epcCode').value.trim();
            const operationType = document.getElementById('operationType').value;
            
            if (!epcCode) {
                showResult('❌ Please enter an EPC code', 'error');
                return;
            }
            
            if (epcCode.length !== 24) {
                showResult('⚠️ EPC code should be 24 characters (currently: ' + epcCode.length + ')', 'error');
                return;
            }
            
            showResult('⏳ Sending scan to API...', 'loading');
            
            try {
                const response = await fetch(API_URL + '/rfid/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        epc_code: epcCode,
                        operation_type: operationType,
                        device_id: 'browser_test',
                        scanned_by: 'web_tester',
                        quantity: 1
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    scanCount++;
                    
                    const product = data.data.product;
                    const tagInfo = data.data.tag_info;
                    
                    let message = `✅ SCAN SUCCESS!\n\n`;
                    message += `Operation: ${operationType.toUpperCase()}\n`;
                    message += `EPC: ${epcCode}\n\n`;
                    message += `📦 Product: ${product.name}\n`;
                    message += `📋 SKU: ${product.sku}\n`;
                    message += `📊 Stock: ${product.quantity}\n`;
                    
                    if (tagInfo.auto_created) {
                        message += `\n🆕 NEW TAG AUTO-CREATED!\n`;
                        message += `This tag was not in the database and was automatically added.`;
                    }
                    
                    showResult(message, 'success');
                    addToHistory(epcCode, operationType, product, tagInfo.auto_created);
                    
                } else {
                    let errorMsg = `❌ ${data.message || 'Scan failed'}\n\n`;
                    
                    if (data.help) {
                        errorMsg += `💡 ${data.help}\n\n`;
                    }
                    
                    if (response.status === 404) {
                        errorMsg += `This tag is not registered in the system.\n`;
                        errorMsg += `Try using RECEIVING operation to auto-create it.`;
                    }
                    
                    showResult(errorMsg, 'error');
                }
                
            } catch (error) {
                showResult(`❌ Network Error!\n\n${error.message}\n\nCheck if Laravel is running.`, 'error');
            }
        }

        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = message;
            resultDiv.className = type;
            resultDiv.style.display = 'block';
        }

        function addToHistory(epc, operation, product, autoCreated) {
            const historyDiv = document.getElementById('scanHistory');
            const logDiv = document.getElementById('scanLog');
            
            logDiv.style.display = 'block';
            
            const time = new Date().toLocaleTimeString();
            const item = document.createElement('div');
            item.className = 'scan-item';
            
            let badge = autoCreated ? '🆕 NEW' : '✓';
            
            item.innerHTML = `
                <strong>${badge} Scan #${scanCount}</strong> - ${time}<br>
                Operation: ${operation.toUpperCase()}<br>
                Product: ${product.name} (${product.sku})<br>
                Stock: ${product.quantity}<br>
                EPC: <code>${epc.substring(0, 16)}...</code>
            `;
            
            historyDiv.insertBefore(item, historyDiv.firstChild);
            
            // Keep only last 10 scans
            while (historyDiv.children.length > 10) {
                historyDiv.removeChild(historyDiv.lastChild);
            }
        }

        // Allow Enter key to submit
        document.getElementById('epcCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendScan();
            }
        });

        // Generate a random EPC on load
        window.onload = function() {
            generateRandomEpc();
        };
    </script>
</body>
</html>
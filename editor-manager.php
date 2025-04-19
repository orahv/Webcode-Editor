<?php
session_start(); 
// Check if the session username is set and matches 'var'
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'var') {
    // Send 403 status code (Forbidden)
    http_response_code(403);

    // Display "Access Denied" message and a "Go Home" button
    echo "
        <html>
            <head>
                <title>Access Denied</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                        margin-top: 50px;
                    }
                    button {
                        padding: 10px 20px;
                        background-color: #007acc;
                        color: white;
                        border: none;
                        border-radius: 5px;
                        cursor: pointer;
                    }
                    button:hover {
                        background-color: #005f99;
                    }
                </style>
            </head>
            <body>
                <h1>Access Denied</h1>
                <p>You do not have permission to access this page.</p>
                <button onclick='window.location.href=\"https://your-website.com/home\"'>Go Home</button>
            </body>
        </html>
    ";
    exit(); // Terminate the script
}
?> 
<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <title>My WebCode Editor</title> 
    <!-- ✅ Monaco Editor via assets -->
    <script src="assets/monaco/loader.js"></script>
    <link rel="stylesheet" type="text/css" href="assets/css/editor.css">
</head> 
<body> 
    <div id="main-container">
        <div id="sidebar">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;">Files</h3>
                <div>
                    <button onclick="createNew('file')" title="New File">📄</button>
                    <button onclick="createNew('folder')" title="New Folder">📁</button>
                    <input type="hidden" id="selected-folder" value="">
                </div>
            </div>

            <input type="text" id="search-bar" placeholder="Search files..." oninput="searchFileTree()">
            <div id="file-tree">Loading files...</div>
        </div>

        <div id="editor-container">
            <div style="display: flex; justify-content: space-between; padding: 5px;">
                <div>
                    <input type="hidden" id="filepath">
                    <button onclick="toggleSidebar()">☰ </button>
                    <button onclick="toggleFullScreen()">⛶ </button>      <button onclick="saveFile()">💾 Save</button>
                </div>
                <button onclick="goHome()">🏠 Back to Home</button>
            </div>
            <div id="editor"></div>
            <div id="quick-preview" style="display: none;">
                <pre id="quick-code"></pre>
            </div>
        </div>
  

<script>
  function goHome() {
    window.location.href = 'https://your-website/home'; // Change this URL to your site
  }
</script>

    </div>
    </div>
    <script>function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }

        let isFullScreen = false;

        function toggleFullScreen() {
            const editorContainer = document.getElementById('editor-container');
            const sidebar = document.getElementById('sidebar');
            const body = document.body;
            const html = document.documentElement;

            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.warn('Fullscreen error:', err);
                });

                sidebar.style.display = 'none';
                editorContainer.classList.add('fullscreen-editor');
                body.classList.add('fullscreen-mode');
                html.classList.add('fullscreen-mode');
            } else {
                document.exitFullscreen();

                sidebar.style.display = 'block';
                editorContainer.classList.remove('fullscreen-editor');
                body.classList.remove('fullscreen-mode');
                html.classList.remove('fullscreen-mode');
            }
        }


    </script>
    <script>
        let editor;
        let fileTreeData = []; 

         
        require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' } });
        require(['vs/editor/editor.main'], function () {
            editor = monaco.editor.create(document.getElementById('editor'), {
                value: '',
                language: 'html',
                theme: 'vs-dark'
            });
        });

 
        fetch('file-manager.php?action=list')
            .then(res => res.json())
            .then(data => {
                fileTreeData = data; 
                const tree = document.getElementById('file-tree');
                tree.innerHTML = '';
                renderFileTree(tree, data);
            });

       
        function renderFileTree(container, items, parentPath = '', highlightPath = '') {
            container.innerHTML = ''; 

            items.forEach(item => {
                const fullPath = parentPath ? `${parentPath}/${item.name}` : item.name;

                if (item.type === 'folder') {
                    const folderDiv = document.createElement('div');
                    folderDiv.className = 'folder';
                    folderDiv.textContent = '📁 ' + item.name;

                    const childrenContainer = document.createElement('div');
                    childrenContainer.style.marginLeft = '15px';
                    childrenContainer.style.display = 'none';

                    folderDiv.onclick = () => {
                        document.querySelectorAll('.file, .folder').forEach(el => el.classList.remove('selected'));
                        folderDiv.classList.add('selected');
                        childrenContainer.style.display = (childrenContainer.style.display === 'none') ? 'block' : 'none';
                        document.getElementById('selected-folder').value = fullPath;
                    };

                    // Highlight newly created folder
                    if (fullPath === highlightPath) {
                        folderDiv.classList.add('selected');
                        childrenContainer.style.display = 'block';
                        document.getElementById('selected-folder').value = fullPath;
                    }

                    container.appendChild(folderDiv);
                    container.appendChild(childrenContainer);

                    renderFileTree(childrenContainer, item.children, fullPath, highlightPath);
                } else if (item.type === 'file') {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'file';
                    fileDiv.textContent = '📄 ' + item.name;

                    fileDiv.onclick = () => {
                        document.querySelectorAll('.file, .folder').forEach(el => el.classList.remove('selected'));
                        fileDiv.classList.add('selected');
                        openFile(fullPath);
                        const folderPath = fullPath.substring(0, fullPath.lastIndexOf('/'));
                        document.getElementById('selected-folder').value = folderPath;
                    };

                    // Context Menu for Rename and Delete
                    fileDiv.oncontextmenu = (e) => {
                        e.preventDefault();
                        const action = prompt('Choose action: Rename or Delete', 'Rename/Delete');
                        if (action === 'Rename') {
                            const newName = prompt('Rename file to:', item.name);
                            if (newName && newName !== item.name) {
                                renameFile(fullPath, newName);
                            }
                        } else if (action === 'Delete') {
                            const confirmDelete = confirm(`Are you sure you want to delete ${item.name}?`);
                            if (confirmDelete) {
                                deleteFile(fullPath);
                            }
                        }
                    };

                    // Highlight newly created file
                    if (fullPath === highlightPath) {
                        fileDiv.classList.add('selected');
                        const folderPath = fullPath.substring(0, fullPath.lastIndexOf('/'));
                        document.getElementById('selected-folder').value = folderPath;
                    }

                    container.appendChild(fileDiv);
                }
            });
        }

        // Load selected file into editor
        function openFile(file) {
            fetch(`file-manager.php?action=open&file=${encodeURIComponent(file)}`)
                .then(res => res.text())
                .then(text => {
                    document.getElementById('filepath').value = file;
                    editor.setValue(text);
                })
                .catch(err => alert("Error opening file: " + err));
        }

        // Search through the file tree
        function searchFileTree() {
            const query = document.getElementById('search-bar').value.toLowerCase();
            const filteredData = fileTreeData.filter(item => searchRecursive(item, query));
            const tree = document.getElementById('file-tree');
            tree.innerHTML = '';  
            renderFileTree(tree, filteredData);  
        }

        function searchRecursive(item, query) {
            if (item.name.toLowerCase().includes(query)) {
                return true;
            }

            if (item.type === 'folder' && item.children) {
                return item.children.some(child => searchRecursive(child, query));
            }

            return false;
        }

        // Save edited code
        function saveFile() {
            const filepath = document.getElementById('filepath').value;
            const code = editor.getValue();

            fetch('save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `filepath=${encodeURIComponent(filepath)}&code=${encodeURIComponent(code)}`
            })
                .then(res => res.text())
                .then(response => {
                    alert('✅ File saved successfully!');
                    openFile(filepath);
                })
                .catch(err => {
                    alert('❌ Save failed: ' + err);
                });
        }

        // Rename File
        function renameFile(oldPath, newName) {
            fetch('file-manager.php?action=rename', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `old=${encodeURIComponent(oldPath)}&new=${encodeURIComponent(newName)}`
            })
                .then(res => res.text())
                .then(response => {
                    alert(response);
                    refreshFileTree();
                })
                .catch(err => alert("Rename failed: " + err));
        }

        // Delete File
        function deleteFile(filePath) {
            fetch('file-manager.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `file=${encodeURIComponent(filePath)}`
            })
                .then(res => res.text())
                .then(response => {
                    alert(response);
                    refreshFileTree();
                })
                .catch(err => alert("Delete failed: " + err));
        }

        // Refresh File Tree
        function refreshFileTree() {
            fetch('file-manager.php?action=list')
                .then(res => res.json())
                .then(data => {
                    const tree = document.getElementById('file-tree');
                    tree.innerHTML = '';
                    renderFileTree(tree, data);
                });
        }

        //     // Toggle Sidebar
        //     function toggleSidebar() {
        //     const sidebar = document.getElementById('sidebar');
        //     sidebar.classList.toggle('collapsed');
        //   }

        //     // Toggle Fullscreen
        //     function toggleFullScreen() {
        //       const editorContainer = document.getElementById('editor-container');
        //       if (!document.fullscreenElement) {
        //         editorContainer.requestFullscreen();
        //       } else {
        //         document.exitFullscreen();
        //       }
        //     }

        function createNew(type) {
            const name = prompt(`Enter ${type} name:`);
            if (!name) return;

            const parent = document.getElementById('selected-folder').value;

            fetch('file-manager.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `type=${encodeURIComponent(type)}&name=${encodeURIComponent(name)}&parent=${encodeURIComponent(parent)}`
            })
                .then(res => res.json())  createNew
                .then(response => {
                    console.log('Response:', response); 

                    if (response.status === 'success') {
                        alert(response.message);

                         
                        const sanitizedPath = response.path.replace(/\\/g, '/');
                        console.log('Sanitized Path:', sanitizedPath); 

  
                        fetch('file-manager.php?action=list')
                            .then(res => res.json())
                            .then(data => {
                                const tree = document.getElementById('file-tree');
                                tree.innerHTML = '';  
                                renderFileTree(tree, data);

                               
                                const newItem = document.querySelector(`[data-path="${sanitizedPath}"]`);
                                if (newItem) {
                                    newItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    newItem.classList.add('highlight');  
                                }

                          
                                if (type === 'file') {
                                    console.log('Redirecting to:', sanitizedPath);  
                                    window.location.href = sanitizedPath;
                                }
                            })
                            .catch(err => alert("Failed to reload file tree: " + err));
                    } else {
                        alert('❌ Error: ' + response.message);  d
                    }
                })
                .catch(err => alert("Creation failed: " + err));
        }


        // Listen for Ctrl+S / Cmd+S to trigger save
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault(); // Prevent browser's default Save Page dialog

                if (editor && document.getElementById('filepath').value) {
                    saveFile();
                } else {
                    alert('No file open to save.');
                }
            }
        });
    </script>

</body>

</html>
const fs = require('fs');
const path = require('path');

function findPreviewPath(startPath, fileExtensions, folderPaths) {
    const files = fs.readdirSync(startPath);

    for (const file of files) {
        const filePath = path.join(startPath, file);

        if (fs.statSync(filePath).isDirectory()) {
            if (folderPaths.includes(file)) {
                return path.join(startPath, file);
            }

            const previewPath = findPreviewPath(filePath, fileExtensions, folderPaths);
            if (previewPath) {
                return previewPath;
            }
        } else {
            const fileExtension = path.extname(file);
            if (fileExtensions.includes(fileExtension)) {
                return path.join(startPath, file);
            }
        }
    }

    return null;
}

const fileExtensions = ['.html', '.php'];
const folderPaths = ['view'];

const workspaceFolder = process.argv[2];
const defaultPreviewPath = findPreviewPath(workspaceFolder, fileExtensions, folderPaths);
if (defaultPreviewPath) {
    console.log(defaultPreviewPath);
}

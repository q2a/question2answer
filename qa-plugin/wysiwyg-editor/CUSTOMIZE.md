Customizing your editor
=============================

This Q2A plugin uses a custom build of CKEditor to keep it simple. However, if you would like to add new features it is straightforward to do so. All languages supported by Q2A are included, but if you are using a different language you can add add it using the tool, or remove all the languages you don't need to create an even smaller plugin.

1. Go to the CKEditor Builder: http://ckeditor.com/builder
2. Click the "Upload build-config.js" button in the top right, and select the `build-config.js` file from the `ckeditor` directory to start with the current config.
3. Use the various controls to modify your build. You can add plugins, choose a different skin and choose the language(s) you require.
4. Make sure "Optimized" is selected and download the custom package.
5. Delete the `ckeditor` folder inside `wysiwyg-editor`, then extract the downloaded package here, replacing the `ckeditor` folder.

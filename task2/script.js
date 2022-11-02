function checkFile(fileldObj)                       // file extension check
{
    let FileName  = fileldObj.value,
        FileExt = FileName.substr(FileName.lastIndexOf('.') + 1),
        FileSize = fileldObj.files[0].size,
        FileSizeMB = (FileSize/5485760).toFixed(2); // load the name of the current file into the session:
    sessionStorage.setItem("currentFileName", 'http://localhost/b1/task2/uploads/' + FileName.substr(FileName.lastIndexOf('\\')+1)); // this is needed to download the html later

    if ( (FileExt !== "xls") || FileSize > 5485760)
    {
        let error = "File type : "+ FileExt+"\n\n";
        error += "Size: " + FileSizeMB + " MB \n\n";
        error += "Please make sure your file is in xls format and less than 5 MB.\n\n";
        document.getElementById("mySubmit").disabled = true;
        alert(error);
        return false;
    }
    document.getElementById("mySubmit").disabled = false;
    return true;
}
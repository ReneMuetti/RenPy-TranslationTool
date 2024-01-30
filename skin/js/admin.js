$(document).ready(function(){
    openAdminNavigation();
    bindClickForNewLanguage();

    bindXliffFileUpload();
    bindDownloadXliff();

    if ( $("#translation").length && $("#process-translations").length ) {
        $("#translation").val( $("#process-translations").is(":checked") );
    }

    if ( $("#process-translations").length ) {
        $("#process-translations").on("change", function() {
            $("#translation").val( $(this).is(":checked") );
        });
    }
});

function resetDownloadInformation()
{
    $("#download-tree span.file > span").html("").css("padding", 0);
}

function resetDownloadElement()
{
    $("#zip-download").attr("href", "").html("");
    $("#download-block").css("display", "none");
}

function downloadZipFromMassAction()
{
    if ( $("#export-dir").val() != "" ) {
        $.ajax({
            "url"   : baseurl + "ajax_xliff_download.php",
            "method": "POST",
            "data"  : {
                          "action"  : "create-zip-file",
                          "exportto": $("#export-dir").val(),
                          "language": $("#download-language option:selected").val(),
                      },
            "beforeSend": function() {
            }
        })
        .done(function(result, status, jqXHR) {
            let data = $.parseJSON(result);

            if ( data.data.error == false ) {
                // create download-link
                $("#zip-download").attr("href", baseurl + "ajax_xliff_download.php?action=download-zip&zip-file=" + data.data.zip);
                $("#zip-download").html(data.data.zip);

                // show link
                $("#download-block").toggle();

                // reset from for new MassAction
                resetDownloadInformation();
                $("#export-dir").val("");
            }
            else {
                alert ( data.message );
            }
        })
        .fail(function(jqXHR, textStatus) {
            alert( "Request failed: " + textStatus );
        });
    }
    else {
    }
}

function downloadMassAction()
{
    let treeFileCounter = 0;
    let treeFileComplete = 0;

    if ( ($("#download-language option:selected").val() != "") && ($("#download-selected option:selected").val() != "") ) {
        if ( $("#export-dir").val() == "" ) {
            // first step -- create export-directory
            $.ajax({
                "url"   : baseurl + "ajax_xliff_download.php",
                "method": "POST",
                "data"  : {
                              "action"  : "create-export-dir",
                          },
                "beforeSend": function() {
                }
            })
            .done(function(result, status, jqXHR) {
                let data = $.parseJSON(result);
                if ( data.data.error == false ) {
                    let exportDirectory = data.data.export;
                    if ( ($("#export-dir").val() == "") && (exportDirectory.length > 2) ) {
                        $("#export-dir").val(exportDirectory);
                        downloadMassAction();
                    }
                    else {
                        alert( "ZIP-Error!!" );
                    }
                }
                else {
                    alert ( data.message );
                }
            })
            .fail(function(jqXHR, textStatus) {
                alert( "Request failed: " + textStatus );
            });
        }
        else {
            // reset download-state
            resetDownloadInformation();
            resetDownloadElement();

            // processing all files in tree
            $.each($("#download-tree li > span.file"), function() {
                let sender = $(this);
                let filepath = sender.attr("data-filenpath");
                let filename = sender.attr("data-filename");

                $.ajax({
                    "url"   : baseurl + "ajax_xliff_download.php",
                    "method": "POST",
                    "data"  : {
                                  "action"  : "download-mass",
                                  "filename": filename,
                                  "filepath": filepath,
                                  "filetype": $("#download-selected option:selected").val(),
                                  "language": $("#download-language option:selected").val(),
                                  "exportto": $("#export-dir").val(),
                              },
                    "beforeSend": function() {
                        //console.log(filepath + ", " + filename);
                        if ( $("#export-dir").val() == "" ) {
                            alert ( messageNoExportDirectorySelect );
                            return false;
                        }
                        treeFileCounter++;
                    }
                })
                .done(function(result, status, jqXHR) {
                    let data = $.parseJSON(result);
                    if ( data.data.error == false ) {
                        sender.children().html( data.data.created ).css("padding", "3px");
                        treeFileComplete++;
                    }
                    else {
                        alert ( data.message );
                    }

                    // console.log(treeFileCounter + ", " + treeFileComplete);
                    if ( treeFileComplete == treeFileCounter ) {
                        // final step -- download ZIP
                        downloadZipFromMassAction();
                    }
                })
                .fail(function(jqXHR, textStatus) {
                    alert( "Request failed: " + textStatus );
                });
            });
        }
    }
    else {
        if ( $("#download-language option:selected").val() == "" ) {
            alert( messageNoLanguageSelect );
            return false;
        }

        if ( $("#download-selected option:selected").val() == "" ) {
            alert( messageNoTypeSelect );
            return false;
        }
    }
}

function bindDownloadXliff()
{
    if ( $("#download-tree").length ) {
        $("#download-tree span").on("click", function() {
            let sender = $(this);
            let filepath = sender.attr("data-filenpath");
            let filename = sender.attr("data-filename");

            $.ajax({
                "url"   : baseurl + "ajax_xliff_download.php",
                "method": "POST",
                "data"  : {
                              "action"  : "download",
                              "filename": filename,
                              "filepath": filepath,
                              "filetype": $("#download-selected option:selected").val(),
                              "language": $("#download-language option:selected").val(),
                          },
                "beforeSend": function() {
                    console.log(filepath + ", " + filename);
                    console.log( $("#download-selected option:selected").val() );

                    if ( $("#download-language option:selected").val() == "" ) {
                        alert( messageNoLanguageSelect );
                        return false;
                    }

                    if ( $("#download-selected option:selected").val() == "" ) {
                        alert( messageNoTypeSelect );
                        return false;
                    }
                }
            })
            .done(function(data, status, jqXHR) {
                if ( status == "success" && jqXHR.status == 200 ) {
                    // OK
                    let dlFilename;
                    let blob;

                    let isIE = false || !!document.documentMode;

                    if ( $("#download-selected option:selected").val() == "xliff" ) {
                        dlFilename = filename.replace(".rpy", ".xliff");
                        blob = new Blob([jqXHR.responseText], {"type": "application/x-xliff+xml"});
                    }
                    else {
                        dlFilename = filename;
                        blob = new Blob([jqXHR.responseText], {"type": "text/rpy"});
                    }

                    if ( isIE ) {
                        window.navigator.msSaveBlob(blob, dlFilename);
                    }
                    else {
                        let thisDate = setCurrentDateTimeFromDownload();
                        sender.children().html( thisDate ).css("padding", "3px");

                        let url = window.URL || window.webkitURL;
                        let link = url.createObjectURL(blob);

                        let a = $("<a></a>", {
                                    "download": dlFilename,
                                    "href"    : link
                                });
                        $("body").append(a);
                        a[0].click();
                        $("body").remove(a);
                        url.revokeObjectURL(link);
                    }
                }
                else {
                    // Failed
                    alert( "Request failed: " + jqXHR.status );
                }
            })
            .fail(function(jqXHR, textStatus) {
                alert( "Request failed: " + textStatus );
            });
        });
    }
}

function setCurrentDateTimeFromDownload()
{
    let current = new Date();
    let month = current.getMonth() + 1;
    let output;

    output = current.getFullYear();

    if ( month < 10 ) {
        output += "-0" + month;
    }
    else {
        output += "-" + month;
    }

    if ( current.getDate() < 10 ) {
        output += "-0" + current.getDate();
    }
    else {
        output += "-" + current.getDate();
    }

    if ( current.getHours() < 10 ) {
        output += " 0" + current.getHours();
    }
    else {
        output += " " + current.getHours();
    }
    if ( current.getMinutes() < 10 ) {
        output += ":0" + current.getMinutes();
    }
    else {
        output += ":" + current.getMinutes();
    }
    if ( current.getSeconds() < 10 ) {
        output += ":0" + current.getSeconds();
    }
    else {
        output += ":" + current.getSeconds();
    }

    return output;
}

function openAdminNavigation()
{
    let currUrl = $(location).attr("href");

    if ( currUrl.indexOf("admin_") !== -1 ) {
        if ( $("#admin").length ) {
            $("#admin").toggleClass(activeClass);
            $("#admin").next("ul").toggleClass(activeClass);
        }
    }
}

function bindClickForNewLanguage()
{
    if ( $("#new-language").length ) {
        $("#new-language").click(function() {
            $("#popup-overlay").toggleClass("active");
        });
    }
}
function closeLanguagePopup()
{
    $("#lng-code").val("");
    $("#popup-overlay").toggleClass("active");
}

function saveNewLanguage()
{
    $.ajax({
        "url"   : baseurl + "ajax_language.php",
        "method": "POST",
        "data"  : {
                      "action" : "newlanguage",
                      "lngcode": $("#lng-code").val()
                  },
        "beforeSend": function() {
        }
    })
    .done(function(result){
        let data = $.parseJSON(result);
        if ( data.error == false ) {
            $(".lang-list div:last").before( data.data );
            closeLanguagePopup();
        }
        else {
            alert( data.data );
            console.log(data);
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });

}

function createProtocolBlock(message)
{
    let newBlock = $("<div></div>", {
                       "class": "glass-border",
                       "html" : message
                   });
    $("#protocol").prepend(newBlock);
}

function bindXliffFileUpload()
{
    if ( $("#xlif-file").length ) {
        $("#xlif-file").on("submit", function(event) {
            event.preventDefault();

            $.ajax({
                "method": "POST",
                "url"   : baseurl + "ajax_upload_xliff.php",
                "data"  : new FormData(this),
                "cache" : false,
                "contentType": false,
                "processData": false,
                "beforeSend" : function() {
                    $("#protocol").html("");
                }
            })
            .done(function(result){
                let data = $.parseJSON(result);

                createProtocolBlock(data.message);

                if ( data.error == false ) {
                    $("#currstep").val(data.currstep);
                    $("#nexstep").val(data.nexstep);
                    $("#directory").val(data.directory);

                    setTimeout(function(){
                        processXliffFile();
                    }, 1500);
                }
                else {
                    alert( "Error!" );
                }
            })
            .fail(function(jqXHR, textStatus){
                alert( "Request failed: " + textStatus );
            });
        });
    }
}

function processXliffFile()
{
    if ( $("#xlif-file").length ) {
        $.ajax({
            "method": "POST",
            "url"   : baseurl + "ajax_upload_xliff.php",
            "data"  : {
                          "currstep"   : $("#currstep").val(),
                          "nexstep"    : $("#nexstep").val(),
                          "directory"  : $("#directory").val(),
                          "translation": $("#translation").val(),
                      },
            "beforeSend" : function() {
            }
        })
        .done(function(result){
            let data = $.parseJSON(result);

            createProtocolBlock(data.message);

            if ( data.error == false ) {
                $("#currstep").val(data.currstep);
                $("#nexstep").val(data.nexstep);
                $("#directory").val(data.directory);

                if ( data.done == false ) {
                    setTimeout(function(){
                        processXliffFile();
                    }, 500);
                }
                else {
                    // Reset Upload-Data
                    resetUplaodData();
                }
            }
            else {
                alert( "Error!" );

                // Reset Upload-Data
                resetUplaodData();
            }
        })
        .fail(function(jqXHR, textStatus){
            alert( "Request failed: " + textStatus );
        });
    }
}

function resetUplaodData()
{
    $("#currstep").val("0");
    $("#nexstep").val("1");
    $("#directory").val("");
    $("#xliff").val(null);
}

function searchDoublicateTranslations()
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_xliff_dublicate.php",
        "data"  : {
                      "action": "serach"
                  },
        "beforeSend" : function() {
                           $("#protocol").html("");
                           foundIds = '';
                       }
    })
    .done(function(result){
        let ajaxData = $.parseJSON(result);

        $("#protocol").html( ajaxData.data.details );
        foundIds = ajaxData.data.ids;
        //console.log(foundIds);
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

function deleteDoublicateTranslations()
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_xliff_dublicate.php",
        "data"  : {
                      "action": "delete",
                      "size"  : foundIds.length,
                      "ids"   : foundIds,
                  },
        "beforeSend" : function() {
                           $("#protocol").html("");
                       }
    })
    .done(function(result){
        let ajaxData = $.parseJSON(result);

        $("#protocol").html( ajaxData.message + "<br />" + ajaxData.data );
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

function createNewDatabaseBackup()
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_db_backup.php",
        "beforeSend" : function() {
                           $("#protocol").append( "<div>" + str_backup_start + "</div>" );
                       }
    })
    .done(function(result){
        let ajaxData = $.parseJSON(result);

        $("#protocol").append( "<div>" + ajaxData.message + "</div>" )
                      .append( "<div>" + str_backup_end + "</div>" );

        updateBackupTableLines();
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

function deleteBackupFile(deleteFileName)
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_db_backup.php",
        "data"  : {
                      "action": "delete",
                      "filename": deleteFileName
                  }
    })
    .done(function(result){
        updateBackupTableLines();
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

function updateBackupTableLines()
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_db_backup.php",
        "data"  : {
                      "action": "update"
                  },
        "beforeSend" : function() {
                           $("#backup-table tbody").html( "" );
                       }
    })
    .done(function(result){
        let ajaxData = $.parseJSON(result);

        $("#backup-table tbody").html( ajaxData.message );
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
    // #backup-table
}
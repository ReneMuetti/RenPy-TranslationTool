$(document).ready(function() {
    // currently nothing
});

function bindEventForTextarea(senderID)
{
    $("#input-" + senderID).on("input", function() {
        let textarea = $(this);

        setTimeout(function() {
            $.ajax({
                "method": "POST",
                "url"   : baseurl + "ajax_html_convert.php",
                "data"  : {
                              "action": "convert",
                              "string": textarea.val()
                          },
                "beforeSend": function() {}
            })
            .done(function(ajaxResult) {
                let ajaxData = $.parseJSON(ajaxResult);

                if ( ajaxData.error == true ) {
                    alert(ajaxData.message);
                }
                else {
                    $("#input-" + senderID).val(ajaxData.data.text);
                    $("#translate-" + senderID).val(ajaxData.data.html);
                }
            })
            .fail(function(jqXHR, textStatus){
                alert( "Request failed: " + textStatus );
            });
        }, 100);
    });
}

/**
 * full-text-search
 */
function searchStringInTranslations()
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_search.php",
        "data"  : {
                      "action"  : "find_translations",
                      "original": $("#original").is(":checked"),
                      "pattern" : $("#search-string").val(),
                      "language": $("#language-selected").find(":selected").val(),
                      "person"  : $("#character-selected").find(":selected").val(),
                  },
        "beforeSend": function() {
                          $("#search-list").html("");
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            $("#search-list").html( ajaxData.message );
        }
        else {
            $("#search-list").html( ajaxData.data );
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

/**
 * change Language with Select-Box
 */
function changeLanguage()
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_details.php",
        "data"  : {
                      "action"  : "load_file_blocks",
                      "language": $("#details-selected").find(":selected").val(),
                  },
        "beforeSend": function() {
                          $("#details-list").html("");
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            $("#details-list").html( ajaxData.message );
        }
        else {
            $("#details-list").html( ajaxData.data );
            loadDetailsPerGameFile();
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

/**
 * toggle DIV and TEXTAREA in Language-Details-List
 * enable Edit-Mode in Detail-List-Per-File
 *
 * @param integer   Translation-ID
 */
function toggleEditTranslation(senderID)
{
    let currState = $("#edit-" + senderID).is(":checked");

    if ( currState == true ) {
        $("#translate-" + senderID).hide();
        $("#input-" + senderID).show();
        $("#save-button-" + senderID).show();
        bindEventForTextarea(senderID);
    }
    else {
        if ( $("#loaded-translation-" + senderID).html() != $("#translate-" + senderID).html() ) {
            // need save change data
            //$("#translate-" + senderID).html( nl2br($("#input-" + senderID).val(), true) );
            convertTranslationIntoHtml(senderID);
            $("#loaded-translation-" + senderID).html( $("#input-" + senderID).val() );
        }

        $("#translate-" + senderID).show();
        $("#input-" + senderID).hide().off("input");
        $("#save-button-" + senderID).hide();
    }
}

/**
 * send Translation for HTML-Convertig to Server
 *
 * @param integer   Translation-ID
 */
function convertTranslationIntoHtml(senderID)
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_string_convert.php",
        "data"  : {
                      "action"     : "convert",
                      "translation": $("#input-" + senderID).val(),
                  },
        "beforeSend": function() {
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            alert( ajaxData.message );
        }
        else {
            // insert new Translation
            $("#translate-" + senderID).html( ajaxData.data );
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

/**
 * Save changes from Inline-Edit on Details-Page
 *
 * @param integer
 */
function saveChangedData(senderID)
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_details.php",
        "data"  : {
                      "action"     : "update_translation_inline",        // Action
                      "translation": $("#input-" + senderID).val(),      // new translation text
                      "uuid"       : $("#uuid-" + senderID).val(),       // UUID
                      "original"   : $("#original-" + senderID).val(),   // Original-ID
                      "data-id"    : senderID,                           // Translation-ID
                  },
        "beforeSend": function() {
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            alert( ajaxData.message );
        }
        else {
            // click checkbox
            $("#message-" + senderID).html( ajaxData.data );
            $("#edit-" + senderID).click();
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

/**
 * load details for all Files in Details-List
 */
function loadDetailsPerGameFile()
{
    $("#details-list .index-lang-details").each(function() {
        let currentID   = $(this).attr("id");
        let currentFile = $(this).attr("data-file");

        $.ajax({
            "method": "POST",
            "url"   : baseurl + "ajax_details.php",
            "data"  : {
                          "action"  : "load_file_details",
                          "filename": currentFile,
                          "language": $("#details-selected").find(":selected").val(),
                      },
            "beforeSend": function() {
                          }
        })
        .done(function(ajaxResult) {
            let ajaxData = $.parseJSON(ajaxResult);

            if ( ajaxData.error == true ) {
                // TODO
            }
            else {
                $("#" + currentID + " .total-count").html( ajaxData.data.total );
                $("#" + currentID + " .translation-percent").html( ajaxData.data.percent );
                $("#" + currentID + " .open-count").html( ajaxData.data.open );
                $("#" + currentID + " .rgb-bar").css({
                                                     "mask"        : ajaxData.data.style,
                                                     "-webkit-mask": ajaxData.data.style,
                                                 });

                $("#" + currentID).parent(".index-language-block").addClass("index-block-clickable").attr("onClick", "loadDetailsFromSingleGameFile(this);");
                $("#" + currentID).toggle("slow");
            }
        })
        .fail(function(jqXHR, textStatus){
            alert( "Request failed: " + textStatus );
        });
    });
}

/**
 * load all strings and translation from selected Game-File
 *
 * @param element   DOM-Element
 */
function loadDetailsFromSingleGameFile(sender)
{
    let currentBlock = $(sender).children(".index-lang-details").attr("id");
    let currentFile  = $(sender).children(".index-lang-details").attr("data-file");
    let currentLang  = $("#details-selected").find(":selected").val();

    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_details.php",
        "data"  : {
                      "action"  : "load_file_translations",
                      "filename": currentFile,
                      "language": currentLang,
                  },
        "beforeSend": function() {
                          $("#details-list").html("");
                          $("#details-selected").prop("selectedIndex", 0);
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            // TODO
        }
        else {
            $("#details-list").html( ajaxData.data );
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}
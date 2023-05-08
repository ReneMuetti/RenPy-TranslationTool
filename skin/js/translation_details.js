$(document).ready(function() {
    // currently nothing
});

function nl2br (str, is_xhtml) {
  // http://kevin.vanzonneveld.net
  // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Philip Peterson
  // +   improved by: Onno Marsman
  // +   improved by: Atli Þór
  // +   bugfixed by: Onno Marsman
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +   improved by: Maximusya
  // *     example 1: nl2br('Kevin\nvan\nZonneveld');
  // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
  // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
  // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
  // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
  // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
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
                      "pattern" : $("#search-string").val(),
                      "language": $("#language-selected").find(":selected").val(),
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
 * toggle DIV and INPUT in Language-Details-List
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
    }
    else {
        if ( $("#input-" + senderID).val() != $("#translate-" + senderID).html() ) {
            // need save change data
            $("#translate-" + senderID).html( nl2br($("#input-" + senderID).val(), true) );
        }

        $("#translate-" + senderID).show();
        $("#input-" + senderID).hide();
        $("#save-button-" + senderID).hide();
    }
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
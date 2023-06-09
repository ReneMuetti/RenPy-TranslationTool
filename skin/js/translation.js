$(document).ready(function() {
    // Init Translation
    loadNextTranslation();
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


function saveCurrentTranslation()
{
    let sourceString = $("#source-sting").html();
    $("#translation-result input[type='text']").each(function() {
        let translatetString = $(this).val();

        if ( translatetString.length > 0 ) {
            // do nothing
        }
        else {
            alert( alertTranslationEmpty );
            return false;
        }

        if ( sourceString != translatetString ) {
            // do nothing
        }
        else {
            alert( alertTranslationIdentical );
            return false;
        }
    });

    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_translation.php",
        "data"  : {
                      "action"   : "save_translation",
                      "lastUUID" : $("#uuid").val(),
                      "languages": $("#desitnation-langs").val(),
                      "allMD5"   : $("#save-by-md5").is(":checked"),
                  },
        "beforeSend": function() {
                          //let allInputs = $("#translation-result input[type='text']").serialize();
                          let allInputs = $("#translation-result textarea").serialize();
                          this.data += "&" + allInputs;
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            alert( ajaxData.message );
        }
        else {
            $("#pre-person-emote").attr( "src", $("#person-emote").attr("src") );

            //let newFirstTranslation = $("#translation-result").find("input").first().val();
            let newFirstTranslation = $("#translation-result").find("textarea").first().val();
            newFirstTranslation = nl2br(newFirstTranslation, true);

            $("#pre-source-sting").html( $("#source-file").val() + "\n<hr /><br />\n" + newFirstTranslation );

            loadNextTranslation();
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

function searchForTranslation()
{
    loadNextTranslation();
}

function loadNextTranslation()
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_translation.php",
        "data"  : {
                      "action"   : "load_translation",
                      "lastUUID" : $("#uuid").val(),
                      "languages": $("#desitnation-langs").val(),
                      "shows"    : $("#show-langs").val(),
                      "common"   : $("#show-common").is(":checked"),
                  },
        "beforeSend": function(jqXHR, settings) {
                          $("#translation-status #status").html("");
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            alert( ajaxData.message );
        }
        else {
            if ( ajaxData.data.done == true ) {
                $("#translation-status #status").html( ajaxData.message );

                loadStringsIntoElement($("#desitnation-langs").val(), "");
                loadStringsIntoElement($("#show-langs").val(), "");

                loadImage("");

                $("#uuid").val("");
                $("#source-file").val("");
                $("#general-id").val("");
                $("#original-id").val("");

                $("#source-sting").html("");

                $("#pre-person-emote").attr("src", baseurl + "skin/images/default.png");
                $("#pre-source-sting").html("");
            }
            else {
                loadImage(ajaxData.data.imagename);

                $("#uuid").val( ajaxData.data.uuid );
                $("#source-file").val( ajaxData.data.filename );
                $("#general-id").val( ajaxData.data.general_id );
                $("#original-id").val( ajaxData.data.original_id );

                $("#translation-status #status").html( ajaxData.data.status );

                $("#source-sting").html( ajaxData.data.source );

                loadStringsIntoElement($("#desitnation-langs").val(), ajaxData.data);
                loadStringsIntoElement($("#show-langs").val(), ajaxData.data);
            }
        }

        //console.log(ajaxData);
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

function loadStringsIntoElement(ElementParams, loadData)
{
    if ( ElementParams.indexOf(",") > -1 ) {
        var arrElement = ElementParams.split(",");
        $.each( arrElement, function(index, value) {
            if ( typeof loadData[value] === "undefined" || loadData[value].length == 0 ) {
                // do nothing
            }
            else {
                if ( $("#destination-" + value).length ) {
                    $("#destination-" + value).val( loadData[value] );
                }
                else {
                    $("#pseudo-" + value).html( loadData[value] );
                }
            }
        });
    }
    else {
        if ( $("#destination-" + ElementParams).length ) {
            $("#destination-" + ElementParams).val( loadData[ElementParams] );
        }
        else {
            $("#pseudo-" + ElementParams).html( loadData[ElementParams] );
        }
    }
}

function loadImage(imageName)
{
    if ( imageName.length ) {
        $("#person-emote").attr("src", baseurl + "skin/images/emotes/" + imageName);
    }
    else {
        $("#person-emote").attr("src", baseurl + "skin/images/default.png");
    }
}
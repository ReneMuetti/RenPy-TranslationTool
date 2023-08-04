$(document).ready(function() {
    // Init Translation
    loadNextTranslation();

    // init Event for "textarea"
    bindEventForTextarea();
});

function getCurrentDivText() {
    let currentDivText = $("#source-string").text();
    //console.log("Current DIV Text:", currentDivText);
    return currentDivText;
}

function bindEventForTextarea()
{
    $("textarea").on("input", function() {
        let textarea = $(this);

        setTimeout(function() {
            //let currentDivText = getCurrentDivText();

            //if ( currentDivText.includes("«") ) {
                let orgTranslation = textarea.val();
                let newTranslation = replaceQuotes(orgTranslation);

                textarea.val(newTranslation);
            //}
        }, 100);
    });
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
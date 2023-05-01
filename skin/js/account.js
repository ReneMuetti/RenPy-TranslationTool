$(document).ready(function() {
    addClickToGuiLanguageImage();
    addClickToTranslationLanguageSelection();
});

function changeCurrentLanguage(newCode)
{
    if ( newCode.length ) {
        $("#selectLanguage").val(newCode);
    }
}

function addClickToGuiLanguageImage()
{
    if ( $(".account-language-list").length ) {
        $(".account-lang-image").click(function(){
            changeCurrentLanguage( $(this).attr("data-code") );
            
            // Remove all Select-Classes
            $(".account-lang-image").removeClass("current-language");
            $(".account-lang-block").removeClass("current-language");
            
            // Add Select-Classes to current Element
            $(this).addClass("current-language");
            $(this).parent().closest(".account-lang-block").addClass("current-language");
        });
        
        $("#selectLanguage").val( $(".account-lang-image.current-language").attr("data-code") );
    }
}

function addClickToTranslationLanguageSelection()
{
    if ( $(".ajax-languages").length ) {
        $(".ajax-languages input").click(function(){
            $.ajax({
                "url"   : baseurl + "ajax_account_language.php",
                "method": "POST",
                "data"  : {
                              "langCode"  : $(this).attr("data-code"),
                              "langAction": $(this).attr("data-action"),
                              "langStatus": $(this).is(":checked"),
                          },
                "beforeSend": function() {
                              }
            })
            .done(function(result){
                let ajaxReturn = $.parseJSON(result);
                console.log(ajaxReturn);
                if ( ajaxReturn.error == true ) {
                    $("img.ajax-save-image").addClass("fadeout-animation");
                    setTimeout(function(){
                        $("img.ajax-save-image").removeClass("fadeout-animation");
                    }, 5000);
                }
                else {
                    alert( ajaxReturn.message );
                }
            })
            .fail(function(jqXHR, textStatus){
                alert( "Request failed: " + textStatus );
            });
        });
    }
}
let pagerCurrent = 1;
let pagerFirst = -1;
let pagerLast = -1;
let newIndex = 1;
let tableRefresh = false;


$(document).ready(function(){
    $("#index-info-table select").on("change", function() {
        updateHistoryTable(this);
    });

    $("#counter").on("change", function() {
        // clear pager
        $("#pager").html("");
        addNewSelectOption($("#pager"), 0, strPleaseSelect);
    });

    if ( $(this).hasClass("no-click") ) {
        $(this).removeClass("no-click");
    }

    $("#pager-back, #pager-forward").on("click", function() {
        $("#pager-back, #pager-forward").removeClass("no-click");

        pagerCurrent = parseInt( $("#pager").find(":selected").val() );
        pagerFirst   = parseInt( $("#pager option:nth-child(2)").val() );
        pagerLast    = parseInt( $("#pager option").last().val() );

        if ( $(this).attr("id") == "pager-forward" ) {
            // page up
            if ( pagerCurrent < pagerLast ) {
                newIndex = pagerCurrent + 1;
                tableRefresh = true;
            }
            else {
                newIndex = pagerCurrent;
            }
        }
        else {
            // page down
            if ( pagerCurrent > pagerFirst ) {
                newIndex = pagerCurrent - 1;
                tableRefresh = true;
            }
            else {
                newIndex = pagerCurrent;
            }
        }

        if ( ($(this).attr("id") == "pager-forward") && (newIndex == pagerLast) ) {
            $(this).addClass("no-click");
        }
        else if ( ($(this).attr("id") == "pager-back") && (newIndex == pagerFirst) ) {
            $(this).addClass("no-click");
        }

        $("#pager").prop("selectedIndex", newIndex);

        if ( tableRefresh == true ) {
            updateHistoryTable(this);
        }
    });

    updateHistoryTable(self);
});

function updateHistoryTable(element)
{
    $.ajax({
        "method": "POST",
        "url"   : baseurl + "ajax_history.php",
        "data"  : {
                      "action"    : "load_history",
                      "user"      : $("#user").find(":selected").val(),
                      "language"  : $("#language").find(":selected").val(),
                      "method"    : $("#method").find(":selected").val(),
                      "counter"   : $("#counter").find(":selected").val(),
                      "pager"     : $("#pager").find(":selected").val(),
                      "uuid"      : $("#uuid").val(),
                      "old-string": $("#old-string").val(),
                      "new-string": $("#new-string").val(),
                      "start-id"  : parseInt( $("#start-id").val() ),
                      "end-id"    : parseInt( $("#end-id").val() ),
                  },
        "beforeSend": function() {
                          $("#index-info-table tbody").html("");
                      }
    })
    .done(function(ajaxResult) {
        let ajaxData = $.parseJSON(ajaxResult);

        if ( ajaxData.error == true ) {
            alert( ajaxData.message );
        }
        else {
            if ( ajaxData.data.total < 1 ) {
                newRowForEmptyResult( ajaxData.data.message );
            }
            else {
                $.each( ajaxData.data.result, function(index, value){
                    newRowWithResultData(value);
                });

                fillTablePager(ajaxData.data.total, $("#counter").find(":selected").val());

                $("#start-id").val( ajaxData.data.general.firstid );
                $("#end-id").val( ajaxData.data.general.lastid );

                if ( ajaxData.data.total <= parseInt( $("#counter").find(":selected").val() ) ) {
                    $("#pager-forward, #pager-back").addClass("no-click");
                }
            }
        }
    })
    .fail(function(jqXHR, textStatus){
        alert( "Request failed: " + textStatus );
    });
}

function newRowForEmptyResult(message)
{
    let newRow = $("<tr></tr>");
    let newCell = $("<td></td>", {
                      "colspan": $("#index-info-table").find("col").length,
                      "html"   : message
                  });
    newRow.append(newCell);
    $("#index-info-table tbody").append(newRow);
}

function newRowWithResultData(lineData)
{
    let newRow = $("<tr></tr>");

    $.each(lineData, function(lineKey, lineValue) {
        newRow.append( createNewTableCell(lineValue) );
    });

    $("#index-info-table tbody").append(newRow);
}

function createNewTableCell(cellContent)
{
    return newCell = $("<td></td>", { "html": cellContent });
}

function fillTablePager(maxCount, perPage)
{
    if ( (maxCount != $("#max-count").val()) || (maxCount >= 1 && $("#pager option").length == 1) ) {
        $("#max-count").val( maxCount );

        $("#pager").html("");
        addNewSelectOption($("#pager"), 0, strPleaseSelect);

        let pages = Math.ceil(maxCount / perPage);
        for( let i = 1; i <= pages; i++ ) {
            addNewSelectOption($("#pager"), i, i);
        }

        $("#pager").prop("selectedIndex", newIndex);
    }


}

function addNewSelectOption(element, value, caption)
{
    let newOption = $("<option></option>", {
                        "value": value,
                        "html" : caption
                    });
    element.append(newOption);
}

function resetFilter()
{
    $("#user").prop("selectedIndex", 0);
    $("#language").prop("selectedIndex", 0);
    $("#method").prop("selectedIndex", 0);
    $("#counter").prop("selectedIndex", 0);
    $("#pager").prop("selectedIndex", 1);
    $("#uuid").val("");
    $("#old-string").val("");
    $("#new-string").val("");
    $("#start-id").val(1);
    $("#end-id").val(10);

    $("#pager-forward, #pager-back").removeClass("no-click");
    $("#pager-forward").addClass("no-click");

    updateHistoryTable(this);
}
function buildMonthlyData(month) {
    //$('.monthlyData').addClass('event-container');
    let stoul = "stourile cele mai tari";
    //$('.monthlyData').html('</p><div class="event-icon"><div class="event-bullet-pontare-bilunara" style="background-color:#8773c1"></div></div><div class="event-info"><p class="event-title">Pontaje 1-15</p><p class="event-desc">' + stoul + '</p></div></div></p>');
    //$('.monthlyData').append('</p><div class="event-icon"><div class="event-bullet-pontare-bilunara" style="background-color:#8773c1"></div></div><div class="event-info"><p class="event-title">Pontaje 15-31</p><p class="event-desc">' + stoul + '</p></div></div></p>');
    //let markup = '<table class="calendar-table"><tbody>><tr class="calendar-header"><td class="calendar-header-day">1-15</td><td class="calendar-header-day">15-31</td></tr><tr class="calendar-body center-align"><td style="font-size: 14px;">'+stoul+'</td><td style="font-size: 14px;">'+stoul+'</td></tr></tbody></table>';
    let wrkDate = getSelectedDate("01 " + month).split('-');
    let primaParte = hoursWorked(month, 1);
    let adouaParte = hoursWorked(month, 2);
    let markup = '<ul class="collapsible"><li><div class="collapsible-header"><i class="material-icons">timeline</i>Ore lucrate</div><div class="collapsible-body">'+
                  '<table class="striped centered"><thead><tr><th>1-15</th><th>16-'+getLastDayOfMonth(wrkDate[1])+'</th></tr></thead><tbody>'+
                  '<tr><td> Total: ' + (primaParte[0] + primaParte[1] + primaParte[2]) + ' ore</td>'+
                  '<td> Total: ' + (adouaParte[0] + adouaParte[1] + adouaParte[2]) + ' ore</td>'+
                  '</tr></tbody></table></div></li></ul>';
    $('.monthlyData').html(markup);
    $('.collapsible').collapsible();
  }
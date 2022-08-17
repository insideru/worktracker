var chartedProjects = [];
var secondCharts = false;
var updateHeatMap = false;
var updatePontajDetail = false;
var dates = [];
var curTimesheets = {};
var usersInHeatmap = new Array();

function drawProjectsChart () {
    let chartSeriesData = [];
    let colorArray = [];
    chartedProjects = [];

    projectsObject.forEach(element => {
      if (Number(element.active)) {
        let projActualTime = buildProjectWorkHours(element.id, element.budget);
        let projName = element.name;
        let projBudget = Number(element.budget);
        let projChartData = {};

        if (projBudget == 0) {
          projChartData = {
            x: projName,
            y: projActualTime
          };
        } else {
          projChartData = {
            x: projName,
            y: projActualTime,
            goals: [
              {
                name: 'Ore bugetate',
                value: projBudget,
                strokeWidth: 5,
                strokeHeight: 10,
                strokeColor: '#775DD0'
              }
            ]
          };
        }

        chartedProjects.push(element);
        chartSeriesData.push(projChartData);

        if (projActualTime>projBudget) {
          projBudget == 0 ? colorArray.push('#56bae8') : colorArray.push('#E51C23');
        } else {
          projBudget == 0 ? colorArray.push('#56bae8') : colorArray.push('#00E396');
        }
      }
    });
    let chartHeight = (chartSeriesData.length+1)*40;
    let chartOptions = {
        series: [
        {
          data: chartSeriesData
        }
      ],
        chart: {
        height: chartHeight>110?chartHeight:110,
        type: 'bar',
        toolbar: {
          show: false
        },
        events: {
            click: function(event, chartContext, config) {
              // The last parameter config contains additional information like `seriesIndex` and `dataPointIndex` for cartesian charts
              if (config.dataPointIndex>-1) {
                updateProjectCharts(config.dataPointIndex);
              }
              $('tspan').off('click');
              $('tspan').on('click', function(evt) {
                //alert(evt.target.innerHTML);
                let target = evt.target;
                let parent = target.parentElement;
                let proj_id = getDBidFromName(parent.children[1].innerHTML, 'project');
                if (proj_id != undefined) {
                  popupProjInfo(proj_id);
                  let instance = M.Modal.getInstance($('#viewProj'));
                  instance.open();
                }
              });
            }
          },
      },
      plotOptions: {
        bar: {
          horizontal: true,
          distributed: true,
        }
      },
      colors: colorArray,
      dataLabels: {
        formatter: function(val, opt) {
          const goals =
            opt.w.config.series[opt.seriesIndex].data[opt.dataPointIndex]
              .goals
      
          if (goals && goals.length) {
            return `${val} / ${goals[0].value}`;
          }
          return val
        }
      },
      legend: {
        show: true,
        showForSingleSeries: true,
        customLegendItems: ['On track', 'Buget depasit', 'Durata bugetata'],
        markers: {
          fillColors: ['#00E396', '#E51C23', '#775DD0']
        }
      }
      };

    let chart = new ApexCharts(document.querySelector("#projectsChart"), chartOptions);
    chart.render();
}

function generateHeatMapData(noDays) {
  let minDate = new Date();
  minDate.setDate(minDate.getDate()-noDays);

  //generate timesheets list
  curTimesheets = new Object();
  alltimesheetsObject.forEach(element => {
    let curDate = new Date(element.date);
    curDate.setHours(0, 0, 0);
    //verificam daca e in range
    if (curDate > minDate) {
      //nu a mai fost tipul asta, facem un array unde sa adaugam
      if (curTimesheets[element.collab_id] === undefined) {
        curTimesheets[element.collab_id] = new Object();
      };
      //exista il bagam, altfel il bagam adaugat
      if (curTimesheets[element.collab_id][curDate] === undefined) {
        curTimesheets[element.collab_id][curDate] = Number(element.time);
      } else {
        curTimesheets[element.collab_id][curDate] += Number(element.time);
      }
    }
  });

  //generate data
  let chartSeries = [];
  let uniq = 0;
  dates = new Array();
  let concedii = new Array();

  daysOffObject.forEach(elem => {
    if (concedii[elem.collab_id] === undefined) {
      concedii[elem.collab_id] = new Array();
    }
    let startDate = new Date(elem.startdate);
    startDate.setHours(0, 0, 0, 0);
    let endDate = new Date(elem.enddate);
    endDate.setHours(0, 0, 0, 0);
    let tmpDate = new Date(startDate);
    while (startDate <= tmpDate && tmpDate <= endDate)
    {
      concedii[elem.collab_id].push(new Date(tmpDate));
      tmpDate.setDate(tmpDate.getDate()+1);
    }
  });

  for(let key in curTimesheets) {
    let emplName = getDBNameFromId(key, 'collab');
    let curData = [];
    for (dayDiff = noDays-1; dayDiff >= 0; dayDiff--) {
      let thisDate = new Date();
      thisDate.setHours(0, 0, 0, 0);
      thisDate.setDate(thisDate.getDate()-dayDiff);
      if (!uniq) {
        dates.push(thisDate);
      }
      if (curTimesheets[key][thisDate] === undefined) {
        //if (!isInArray(holidayArray, thisDate) && thisDate.getDay()>0 && thisDate.getDay<6 && isInArray(concedii[key], thisDate))
        if (concedii[key] !== undefined) {
          if (isInArray(concedii[key], thisDate))
          {
            curData.push(20);
          } else {
            curData.push(0);
          }
        } else {
          curData.push(0);
        }
      } else {
        curData.push(curTimesheets[key][thisDate]);
      }
    }
    chartSeries.push({name: emplName, data: curData});
    uniq++;
  }

  chartSeries.forEach(elem => {
    usersInHeatmap.push(elem.name);
  });
  
  //dates.sort((a,b)=>a.getTime()-b.getTime());
  let colors = generateHeatMapColors(noDays);
  let options = {
    series: chartSeries,
    chart: {
    height: uniq = 65 + uniq * 40,
    type: 'heatmap',
    events: {
      click: function(event, chartContext, config) {
        // The last parameter config contains additional information like `seriesIndex` and `dataPointIndex` for cartesian charts
        if (config.globals.series[config.seriesIndex][config.dataPointIndex]>0 && config.globals.series[config.seriesIndex][config.dataPointIndex]<20) {
          drawPontajPerCollab(config.seriesIndex, config.dataPointIndex);
        }
      }
    }
  },
  plotOptions: {
    heatmap: {
      radius: 2,
      shadeIntensity: 0.2,
      colorScale: {
        ranges: [{
          from: 20,
          to: 20,
          color: "#b6d7a8",
          name: "Concedii",
        },{
          from: 21,
          to: 21,
          color: "#008FFB",
          name: "Zile normale",
        },{
          from: 22,
          to: 22,
          color: "#cc0000",
          name: "Weekend-uri",
        },{
          from: 23,
          to: 23,
          color: "#8e7cc3",
          name: "Sarbatori legale",
      }],
        inverse: true,
        min: 0,
        max: 8
      }
    }
  },
  dataLabels: {
    enabled: false
  },
  xaxis: {
    type: 'numeric',
    tickAmount: noDays,
    tickPlacement: 'between',
    labels: {
      show: false
    },
    axisTicks: {
      show: true
    },
    axisBorder: {
      show: false
    }
  },
  tooltip: {
    custom: function({series, seriesIndex, dataPointIndex, w}) {
      //seriesIndex + '-' + dates[dataPointIndex] + '-' +
      let luna = dates[dataPointIndex].getMonth()+1;
      let zi = dates[dataPointIndex].getDate();
      let an = dates[dataPointIndex].getFullYear();
      let lunaChar = luna.toString();
      let ziChar = zi.toString();
      let venirePlecare = getUserAttendance(getDBidFromName(usersInHeatmap[seriesIndex], 'collab'), `${an}-${luna>9?luna:'0'+lunaChar}-${zi>9?zi:'0'+ziChar}`);
      zi = dates[dataPointIndex].getDay();
      switch (luna) {
        case 1: lunaChar = "Ianuarie"; break;
        case 2: lunaChar = "Februarie"; break;
        case 3: lunaChar = "Martie"; break;
        case 4: lunaChar = "Aprilie"; break;
        case 5: lunaChar = "Mai"; break;
        case 6: lunaChar = "Iunie"; break;
        case 7: lunaChar = "Iulie"; break;
        case 8: lunaChar = "August"; break;
        case 9: lunaChar = "Septembrie"; break;
        case 10: lunaChar = "Octombrie"; break;
        case 11: lunaChar = "Noiembrie"; break;
        case 12: lunaChar = "Decembrie"; break;
      }
      switch (zi) {
        case 1: ziChar = "Luni"; break;
        case 2: ziChar = "Marti"; break;
        case 3: ziChar = "Miercuri"; break;
        case 4: ziChar = "Joi"; break;
        case 5: ziChar = "Vineri"; break;
        case 6: ziChar = "Sambata"; break;
        case 0: ziChar = "Duminica"; break;
      }
      let today = dates[dataPointIndex].getDate() + ' ' + lunaChar;
      if (Array.isArray(venirePlecare)) {
        return '<div>' +
        '<span>' + ziChar + ' - ' + today + ' - ' + series[seriesIndex][dataPointIndex] + ` ore</span><br><span>Ora venire: ${venirePlecare[0]} | Ora plecare: ${venirePlecare[1]}</span>` +
        '</div>';
      } else {
        return '<div>' +
        '<span>' + ziChar + ' - ' + today + ' - ' + series[seriesIndex][dataPointIndex] + ` ore</span> ` +
        '</div>';
      }
    }
  },
  colors: colors,
  title: {
    text: 'Pontaje pe ultimele ' + noDays + ' zile'
  }};

  drawHeatMap(options);
}

function generateHeatMapColors(days) {
  //["#FFA500", "#FFA500", "#008FFB", "#008FFB", "#8e7cc3", "#008FFB", "#008FFB", "#008FFB"];
  let minDate = new Date();
  minDate.setHours(0, 0, 0, 0);
  let res = new Array();
  minDate.setDate(minDate.getDate()-days);
  for (diff=1;diff<=days;diff++) {
    minDate.setDate(minDate.getDate()+1);
    if (isInArray(holidayArray, minDate)) {
      //e sarbatoare
      res.push("#8e7cc3");
    } else if (minDate.getDay()==0 || minDate.getDay() == 6) {
      //e weekend
      res.push("#cc0000");
    } else {
      res.push("#008FFB");
    }
  }
  return res;
}

function drawHeatMap (options) {
  if ($('#heatMapChart').html()=='') {
    heatMapChart = new ApexCharts(document.querySelector("#heatMapChart"), options);
    heatMapChart.render();
    updateHeatMap = true;
  } else {
    heatMapChart.updateSeries (options.series, true);
    heatMapChart.updateOptions ({title: {text: 'Pontaje pe ultimele ' + $('#heatmapDays').val() + ' zile'}}, true, true, true);
    heatMapChart.updateOptions ({chart: {height: options.chart.height}}, true, true, true);
    heatMapChart.updateOptions ({colors: options.colors}, true, true, true);
    heatMapChart.updateOptions ({xaxis: {tickAmount: options.xaxis.tickAmount}}, true, true, true);
  }
}

function updateProjectCharts (projID) {
    $('.hide').removeClass("hide");
    if (projID == -1) { return; }
    let wrkData = getActivitiesAndCollabs (chartedProjects[projID].id, projID);
    //graficul de timeline
    let tmlData = buildProjetTimeline (chartedProjects[projID].id, projID);
    let uniq = 0;
    let j = "";
    tmlData.forEach(element => {
      if (element.x != j) {
        j = element.x;
        uniq++;
      }
    });
    uniq = 65 + uniq * 40;
    let tmlChartData = {
      series: [
        {
          name: 'Pontaj',
          data: tmlData
        }],
      chart: {
      height: uniq,
      type: 'rangeBar'
      },
      plotOptions: {
        bar: {
          horizontal: true,
          barHeight: '80%'
        }
      },
      xaxis: {
        type: 'datetime'
      },
      stroke: {
        width: 1
      },
      fill: {
        type: 'solid',
        opacity: 0.6
      },
      legend: {
        show: true,
        showForSingleSeries: true,
        customLegendItems: ['Data Incepere', 'Pontaje', 'Data Predare'],
        markers: {
          fillColors: ['#00FF00', '#008ffb', '#CD2F2A']
        }
      }
    };

    //graficul pe activitati
    let activitiesData = wrkData[0];
    let activitiesChartData = {
        series: [
        {
          data: activitiesData
        }
      ],
        legend: {
        show: false
      },
      colors: ['#50369b'],
      chart: {
        height: 'auto',
        type: 'treemap'
      },
      title: {
        text: chartedProjects[projID].name
      }
      };

      //graficul pe muluci
      let collabData = wrkData[1];
      let collabChartData = {
        series: [
        {
          data: collabData
        }
      ],
        legend: {
        show: false
      },
      colors: ['#136267'],
      chart: {
        height: 'auto',
        type: 'treemap'
      },
      title: {
        text: chartedProjects[projID].name
      }
      };
      
      if (!secondCharts) {
        tmlChart = new ApexCharts(document.querySelector("#projectsTimeline"), tmlChartData);
        tmlChart.render();
        activitiesChart = new ApexCharts(document.querySelector("#projectsActivityChart"), activitiesChartData);
        activitiesChart.render();
        collabChart = new ApexCharts(document.querySelector("#projectsCollabChart"), collabChartData);
        collabChart.render();
        secondCharts = true;
      } else {
        tmlChart.updateSeries ([{data: tmlData}], true);
        tmlChart.updateOptions ({chart: {height: uniq}}, true, true, true);
        activitiesChart.updateOptions ({title: {text: chartedProjects[projID].name}}, true, true, true);
        activitiesChart.updateSeries ([{data: activitiesData}], true);
        collabChart.updateOptions ({title: {text: chartedProjects[projID].name}}, true, true, true);
        collabChart.updateSeries ([{data: collabData}], true);
      }
}

function buildProjectWorkHours (projID, budget) {
  let retval = 0;
  let startDate = new Date();
  startDate.setHours(0, 0, 0, 0);
  startDate.setDate(startDate.getDate()-30);
  alltimesheetsObject.forEach(element => {
      if (element.project_id == projID) {
          if (budget == 0) {
            let curDate = new Date(element.date);
            curDate.setHours(0, 0, 0, 0);
            if (curDate >= startDate) {
              retval+=Number(element.time);
            }
          } else {
            retval+=Number(element.time);
          }   
      }
  });
  return retval;
}

function buildProjetTimeline (projID, deadlineID) {
  let projectTimesheets = new Array(activitiesObject.length);
  let res = new Object();
  alltimesheetsObject.forEach(element => {
    if (element.project_id == projID) {
      if (typeof projectTimesheets[element.activity_id] === 'undefined') {
        projectTimesheets[element.activity_id] = new Array();
      }
      if (!projectTimesheets[element.activity_id].includes(element.date)) {
        if (chartedProjects[deadlineID].budget > 0) {
          projectTimesheets[element.activity_id].push(element.date);
        } else {
          let startDate = new Date();
          startDate.setHours(0, 0, 0, 0);
          startDate.setDate(startDate.getDate()-30);
          let curDate = new Date(element.date);
          curDate.setHours(0, 0, 0, 0);
          if (curDate >= startDate) {
            projectTimesheets[element.activity_id].push(element.date);
          }
        }
      }
    }
  });
  projectTimesheets.forEach((element, index, _array) => {
    let primu = true;
    let iStart = iEnd = new Date;
    for (i=0; i<element.length; i++) {
      let _date = element[i].split('-');
      if (primu) {
        iStart = new Date (Number(_date[0]), Number(_date[1])-1, Number(_date[2]), 0, 0, 0);
        iEnd = new Date (Number(_date[0]), Number(_date[1])-1, Number(_date[2]), 0, 0, 0);
        primu = false;
      } else {
        let curDate = new Date (Number(_date[0]), Number(_date[1])-1, Number(_date[2]), 0, 0, 0);
        curDate.setDate(curDate.getDate() - 1);
        if (curDate - iEnd == 0) {
          iEnd = new Date(Number(_date[0]), Number(_date[1])-1, Number(_date[2]), 0, 0, 0);
        } else {
          if (iStart - iEnd == 0) {
            iEnd.setDate(iEnd.getDate()+1);
          }
          if (typeof res[getDBNameFromId(index, 'activity')] === 'undefined') {
            res[getDBNameFromId(index, 'activity')] = new Array();
          }
          res[getDBNameFromId(index, 'activity')].push(iStart, iEnd);
          iStart = new Date(Number(_date[0]), Number(_date[1])-1, Number(_date[2]), 0, 0, 0);
          iEnd = new Date(Number(_date[0]), Number(_date[1])-1, Number(_date[2]), 0, 0, 0);
        }
      }
    }
    if (!primu) {
      if (iStart - iEnd == 0) {
        iEnd.setDate(iEnd.getDate()+1);
      }
      if (typeof res[getDBNameFromId(index, 'activity')] === 'undefined') {
        res[getDBNameFromId(index, 'activity')] = new Array();
      }
      res[getDBNameFromId(index, 'activity')].push(iStart, iEnd);
    }
  })

  let result = new Array();
  for (const key in res) {
    for (i=0; i<res[key].length; i=i+2) {
      let tempObj = {};
      tempObj['x'] = key;
      tempObj['y'] = [res[key][i].getTime(), res[key][i+1].getTime()];
      if (i+2 == res[key].length && chartedProjects[deadlineID].budget > 0) {
        tempObj['goals'] = [
          {
            name: 'Deadline',
            value: new Date(chartedProjects[deadlineID].deadline).getTime(),
            strokeColor: '#CD2F2A'
          },
          {
            name: 'Incepere',
            value: new Date(chartedProjects[deadlineID].start_date).getTime(),
            strokeColor: '#00ff00'
          }
        ];
      }
      result.push(tempObj);
    }
  }
  return result;
}

function getActivitiesAndCollabs (projID, index) {
    let tmpActivities = {};
    let tmpCollabs = {};
    let res = [];
    let res0 = [];
    let res1 = [];
    let startDate = new Date();
    startDate.setHours(0, 0, 0, 0);
    startDate.setDate(startDate.getDate()-30);
    alltimesheetsObject.forEach(element => {
        if (Number(element.project_id) == projID) {
            let tmpAct = getDBNameFromId(element.activity_id, 'activity');
            let tmpCollab = getDBNameFromId(element.collab_id, 'collab');
            let curDate = new Date(element.date);
            curDate.setHours(0, 0, 0, 0);
            if (isNaN(tmpActivities[tmpAct])) {
              if (chartedProjects[index].budget > 0) {
                tmpActivities[tmpAct] = Number(element.time);
              } else {
                if (curDate >= startDate) {
                  tmpActivities[tmpAct] = Number(element.time);
                }
              }
            } else {
              if (chartedProjects[index].budget > 0) {
                tmpActivities[tmpAct] += Number(element.time);
              } else {
                if (curDate >= startDate) {
                  tmpActivities[tmpAct] += Number(element.time);
                }
              }
            }
            if (isNaN(tmpCollabs[tmpCollab])) {
              if (chartedProjects[index].budget > 0) {
                tmpCollabs[tmpCollab] = Number(element.time);
              } else {
                if (curDate >= startDate) {
                  tmpCollabs[tmpCollab] = Number(element.time);
                }
              }
            } else {
              if (chartedProjects[index].budget > 0) {
                tmpCollabs[tmpCollab] += Number(element.time);
              } else {
                if (curDate >= startDate) {
                  tmpCollabs[tmpCollab] += Number(element.time);
                }
              }
            }
        }
    });
    Object.keys(tmpActivities).forEach(function(key,index) {
        res0.push({x: key, y: tmpActivities[key]});
    });
    Object.keys(tmpCollabs).forEach(function(key,index) {
        res1.push({x: key, y: tmpCollabs[key]});
    });
    res.push(res0);
    res.push(res1);
    return res;
}

function drawPontajChart (date) {
  $("#viewPontajChart").html('');
  let data = [];
  let groups = [];
  let oldName = "", newName = "";
  let counter = 1;
  for (let element of myTimesheets) {
    if (element.date == date) {
      newName = getDBNameFromId(element.project_id, 'project');
      if (element.phase_id != 0) newName+=` - ${getDBNameFromId(element.phase_id, 'phase')}`;
      if (element.milestone_id != 0) newName+=` - ${getDBNameFromId(element.milestone_id, 'milestone')}`;
      //console.log(getDBNameFromId(element.project_id, 'project'), getDBNameFromId(element.activity_id, 'activity'), element.time);
      data.push({x: getDBNameFromId(element.activity_id, 'activity'), y: Number(element.time)});
      if (oldName!=newName) {
        if (oldName!="") {
          groups.push({title: oldName, cols: counter});
        }
        oldName = newName;
        counter = 1;
      } else {
        counter++;
      }
    }
  }
  groups.push({title: oldName, cols: counter});
  let options = {
  series: [{
    name: "Pontaj",
    data: data
  }],
  chart: {
    type: 'bar',
    height: 380
  },
  xaxis: {
    type: 'category',
    labels: {
      formatter: function(val) {
        return val
      }
    },
    group: {
      style: {
        fontSize: '10px',
        fontWeight: 700
      },
      groups: groups
    }
  },
  tooltip: {
    x: {
      formatter: function(val) {
        return val
      }  
    }
  },
  };

  let chart = new ApexCharts(document.querySelector("#viewPontajChart"), options);
  chart.render();
}

function regenChart() {
  $('#projectsChartDiv').html('<div id="projectsChart"></div>');
  drawProjectsChart();
}

function drawPontajPerCollab(collabIndex, dayIndex) {
  let contor = 0;
  //console.log(collabIndex, dayIndex);
  for (let key in curTimesheets) {
    if (contor == collabIndex) {
      //console.log(curTimesheets[key]);
      //console.log(getDBNameFromId(key, 'collab'));
      let lunaChar = "";
      switch (dates[dayIndex].getMonth()+1) {
        case 1: lunaChar = "Ianuarie"; break;
        case 2: lunaChar = "Februarie"; break;
        case 3: lunaChar = "Martie"; break;
        case 4: lunaChar = "Aprilie"; break;
        case 5: lunaChar = "Mai"; break;
        case 6: lunaChar = "Iunie"; break;
        case 7: lunaChar = "Iulie"; break;
        case 8: lunaChar = "August"; break;
        case 9: lunaChar = "Septembrie"; break;
        case 10: lunaChar = "Octombrie"; break;
        case 11: lunaChar = "Noiembrie"; break;
        case 12: lunaChar = "Decembrie"; break;
      }
      $('#numePontaj').html(`Pontaje pentru ${getDBNameFromId(key, 'collab')} in data de ${dates[dayIndex].getDate()} ${lunaChar} ${dates[dayIndex].getFullYear()}`);
      $("#pontajDetailChart").html('');
      let data = [];
      let groups = [];
      let oldName = newName = "";
      let counter = 1;
      
      for (let element of alltimesheetsObject) {
        let curDate = new Date(element.date);
        curDate.setHours(0, 0, 0, 0);
        //console.log(curDate, dates[dayIndex], curDate - dates[dayIndex]);
        if (curDate - dates[dayIndex] == 0 && element.collab_id == key) {
          newName = getDBNameFromId(element.project_id, 'project');
          if (element.phase_id != 0) newName+=` | ${getDBNameFromId(element.phase_id, 'phase')}`;
          if (element.milestone_id != 0) newName+=` | ${getDBNameFromId(element.milestone_id, 'milestone')}`;
          data.push({x: getDBNameFromId(element.activity_id, 'activity'), y: Number(element.time)});
          if (oldName!=newName) {
            if (oldName!="") {
              groups.push({title: oldName, cols: counter});
            }
            oldName = newName;
            counter = 1;
          } else {
            counter++;
          }
        }
      }
      groups.push({title: oldName, cols: counter});
      let options = {
      series: [{
        name: "Pontaj",
        data: data
      }],
      chart: {
        type: 'bar',
        height: 380
      },
      xaxis: {
        type: 'category',
        labels: {
          formatter: function(val) {
            return val;
          }
        },
        group: {
          style: {
            fontSize: '10px',
            fontWeight: 700
          },
          groups: groups
        }
      },
      tooltip: {
        x: {
          formatter: function(val) {
            return val;
          }  
        }
      },
    };
    if (!updatePontajDetail) {
      pontajDetailChart = new ApexCharts(document.querySelector("#pontajDetailChart"), options);
      pontajDetailChart.render();
      updatePontajDetail = true;
    } else {
      pontajDetailChart.updateSeries (options.series, true);
      pontajDetailChart.updateOptions ({xaxis: {group: {groups: options.xaxis.group.groups}}}, true, true, true);
    }
    contor++;
    } else {
      contor++;
    }
  }
}

function getUserAttendance(collab_id, date) {
  let res = new Array();
  for (let element of attendanceObject) {
    if (element.collab_id == collab_id && element.date == date) {
      res[0] = element.start;
      res[1] = element.end;
      return res;
    }
  }
  return 0;
}
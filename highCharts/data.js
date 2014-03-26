$(function() {
				//Highcharts with mySQL and PHP - Ajax101.com
				var months = [];
				var days = [];
				var switch1 = true;
				$.get('values.php', function(data) {

								data = data.split('/');
								for (var i in data) {
												if (switch1 == true) {
																months.push(data[i]);
																switch1 = false;
												} else {
																days.push(parseFloat(data[i]));
																switch1 = true;
												}

								}
								months.pop();

								$('#chart').highcharts({
												chart : {
																				type : 'spline'
																},
												title : {
																				text : 'Highcharts with pgSQL, PHP and AJAX'
																},
												subtitle : {
																					 text : 'My House'
																	 },
												xAxis : {
																				title : {
																												text : 'DateTime'
																								},
												categories : months
																},
												yAxis : {
																				title : {
																												text : 'Temperature'
																								},
												labels : {
																				 formatter : function() {
																														 return this.value 
																										 }
																 }
																},
												tooltip : {
																					crosshairs : true,
																					shared : true,
																					valueSuffix : ''
																	},
												plotOptions : {
																							spline : {
																															 marker : {
																																								radius : 4,
																																								lineColor : '#666666',
																																								lineWidth : 1
																																				}
																											 }
																			},
												series : [{

																				 name : 'Temperature',
																				 data : days
																				 /*dataLabels: {
																								 enabled: true,
																								 rotation: -90,
																								 color: '#FFFFFF',
																								 align: 'right',
																								 x: 4,
																								 y: 10,
																								 style: {
																												 fontSize: '13px',
																												 fontFamily: 'Verdana, sans-serif',
																												 textShadow: '0 0 3px black'
																								 }
																				}*/
																 }]
								});
				});
});

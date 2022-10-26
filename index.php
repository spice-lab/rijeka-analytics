<?php

date_default_timezone_set('Europe/Zagreb');


$q=function(){

	$db=json_decode(file_get_contents('../.sql.json'));

	$conn = new mysqli('localhost', $db->username, $db->password, 'rijeka');
	if ((!$conn)||$conn->connect_error) {
	  die("Connection failed: " . $conn->connect_error);
	}


	class Q{

		protected $conn;
		public function __construct($conn){
			$this->conn=$conn;
		}

		protected function _w($where=null){
			return empty($where)?'':' '.$where;

		}

		public function count($where=null){

			if($results = $this->conn->query('SELECT count(*) as count FROM event'.$this->_w($where))){
				foreach ($results as $result) {
				 	return intval($result['count']);
				 } 
			}
			return 0;
	
		}

		public function countDistinct($field, $where=null){

			if($results = $this->conn->query(
				'SELECT count(*) as count FROM (SELECT DISTINCT '.$field.' FROM event'.$this->_w($where).') as a'
			)){
				foreach ($results as $result) {
				 	return intval($result['count']);
				 } 
			}
			return 0;
	
		}

		public function countDistinctGroups($where=null){

			if($results = $this->conn->query(
				'SELECT count(*) as count, data FROM event'.$this->_w($where).' GROUP BY data'
			)){
				return $results->fetch_all(MYSQLI_ASSOC);
			}
			return [];
	
		}

		public function distributionThreshold($field, $n, $comparator){
			return 'SELECT '.$field.' FROM (SELECT '.$field.', count(*) as count, data FROM event GROUP BY '.$field.') as a WHERE a.count'. $comparator.$n;
		}

		public function distribution($field, $where=null){

			if($results = $this->conn->query(
				'SELECT '.$field.', count(*) as count, data FROM event'.$this->_w($where).' GROUP BY '.$field
			)){
				return $results->fetch_all(MYSQLI_ASSOC);
			}
			return [];
	
		}

		public function distinct($field, $where=null){

			if($results = $this->conn->query(
				'SELECT DISTINCT '.$field.' FROM event'.$this->_w($where)
			)){
				return $results->fetch_all(MYSQLI_ASSOC);
			}
			return [];
	
		}

	}

	return new Q($conn);

};
$q=$q();



$ipmap=json_decode(file_get_contents('../.ipmap.json'), true);
$countries=array();
foreach($ipmap as $ip=>$country){
	if(!array_key_exists($country, $countries)){
		$countries[$country]=0;
	}
	$countries[$country]++;
}


$fileAge=time()-filemtime('../.ipmap.json');
if($fileAge>3600){
	touch('../.ipmap.json');
	shell_exec('php ./geocodeip.php '.escapeshellarg($_SERVER['HTTP_HOST']).' > .log.txt 2>&1 &');
}




?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="viewport" content="width=500, initial-scale=1.3" />
		<title>Analytics</title>
		<script type="text/javascript">
			if(!window.console){
			window.console={
				"log":function(){},
				"error":function(){},
				"warn":function(){},
				"info":function(){}
			}
		}
		</script>
		<script type="text/javascript" src="https://aopfn.geoforms.ca/app/nickolanack/php-core-app/assets/js/ClassObject.js?1655843160"></script>
		<script type="text/javascript" src="https://aopfn.geoforms.ca/app/nickolanack/php-core-app/assets/js/Window.js?1655851684"></script>

		<script type="text/javascript" src="https://d26b395fwzu5fz.cloudfront.net/keen-analysis-1.2.2.js"></script>
		<script type="text/javascript" src="https://d26b395fwzu5fz.cloudfront.net/keen-dataviz-1.1.3.js"></script>
		<link type="text/css" href="https://d26b395fwzu5fz.cloudfront.net/keen-dataviz-1.1.3.css" rel="stylesheet">

		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js" ></script>

		<script type="text/javascript">
			google.charts.load('current', {
				'packages':['geochart'],
			});
			
			google.charts.setOnLoadCallback(drawRegionsMap);
			function drawRegionsMap() {
				var data = google.visualization.arrayToDataTable(([['Country', 'Unique IPs']]).concat(<?php echo json_encode(array_map(function($country)use($countries){

					return array(
						$country,
						$countries[$country]
					);

				}, array_keys($countries)), JSON_PRETTY_PRINT); ?>));
				var options = {
					colorAxis: {colors: ['rgb(161, 199, 211)', 'rgb(0, 187, 222)', 'rgb(254, 102, 114)']},
					region:150
				};
				var chart = new google.visualization.GeoChart(document.getElementById('regions_div'));
				chart.draw(data, options);
			}




			


		</script>
		<style type="text/css">
			

			div#metrics_div>div, div#metrics_tours_div>div, div#metrics_tours_div_active_items>div, div#metrics_tours_div_casual>div {
			    width: 30%;
			    margin: 10px;
			    display: inline-block;
			    vertical-align: top;
			    min-width: 210px;
			}

		

			div#regions_div, div#metrics_div, div#metrics_tours_div, div#metrics_tours_div_active, div#metrics_tours_div_casual, div#chart_12_months, div#chart_12_months_active, div#chart_distribution {
			    max-width: 900px;
			    width: calc( 100% - 100px );
			    margin: 50px auto;
			    text-align: center;
			    vertical-align: top;
			}

			.keen-dataviz-title {
				margin-top: 100px;
			    scale: 2;
			}


			h1 {
			    font-family: sans-serif;
			    font-weight: 100;
			    font-size: 50px;
			    text-align: center;

			    max-width: 900px;
			    width: calc( 100% - 100px );
			    margin: 10px auto;

			}

			h2 {
			    font-family: 'Gotham Rounded SSm A', 'Gotham Rounded SSm B', 'Helvetica Neue', Helvetica, Arial, sans-serif;
			    font-weight: 200;
			    text-align: center;
			    max-width: 900px;
			    width: calc( 100% - 100px );
			    margin: 10px auto;
			    font-size: 28px;
			    color: #4D4D4D;
			    margin-top: 100px;
			}

			
			p {
			    margin: 10px auto;
			    font-family: sans-serif;
			    font-weight: 100;
			    color: #444444;

			    max-width: 900px;
			    width: calc( 100% - 100px );
			}

			h1>span {
			    font-weight: 400;
			    color: #00bbde;
			    font-size: 70px;
			}

			g.c3-axis.c3-axis-y {
			    display: none;
			}

			@media only screen and (max-width: 600px) {
			 	div#metrics_div>div, div#metrics_tours_div>div,  div#metrics_tours_div_active_items>div, div#metrics_tours_div_casual>div {
			 		min-width: unset;
			 		width: 90%;
			 	}
			}


			#metrics_tours_div_casual .keen-dataviz {
			    scale: 0.3;
			    position: relative;
			    top: 70px;
			    left: 90px;
			    
			}

			div#metrics_tours_div_active {
			    position: relative;
			}

			div#metrics_tours_div_casual {
			    position: absolute;
			    top: 16px;
			    left: 35px;
			    margin: 0;
			}


			div#metrics_tours_div_casual .keen-dataviz .keen-dataviz-metric-value{
				color: black;
			}


			label.active-u:after, label.casual-u:after {
			    content: "";
			    display: inline-block;
			    width: 12px;
			    height: 12px;
			    background-color: mediumaquamarine;
			    margin-left: 5px;
			    vertical-align: top;
			    border:1px solid #404040;
			}

			label.casual-u:after {
			     background-color: white;    
			}


		</style>
	</head>
	<body>
		<main>
			<h1>Analytics. <span>Rijeka in Flux</span> mobile app</h1>
			<section>
				<div id="regions_div" style="">
					
				</div>
			<section>

			<section>
				<div id="metrics_div">
					<div id="metric_total"></div>
					<div id="metric_ips"></div>
					<div id="metric_today"></div>
					<div id="metric_7days"></div>
					<div id="metric_month"></div>
					<div id="metric_lastMonth"></div>
				</div>

				<p>
					The app requests section data each time a user navigates to a new section within the app (ie: each curated tour is a section), these 
					are counted as section views.
					<br/><br/>
					The app does not track users and does not collect or store any identifying information about individual users. Therefore, the only data (collected by us) that can be 
					used to indicate a unique user is the IP address; often a device uses the same IP address for a long period of time, unless they switch between networks,
					so that the IP address is an approximate indicator of unique users. Over a long time period, the unique IPs are probably an overestimate of unique users but 
					it is likely to be more accurate over short periods.
					<br/><br/>
					Daily, weekly, and monthly metrics are calculated using unique IPs unless labeled otherwise. Dates and times are formatted for Europe/Zagreb timezone.


				</p>


				<div id="chart_12_months">
				</div>

				


				<h2>Unique section views</h2>
				<p>*These are not the only sections that are logged as `section views`, but are easily distinguished from others. Because some section views do not request unique data, they cannot be identified as a specific app section and are not included below.</p>
				<div id="metrics_tours_div">	
				</div>


				<div id="chart_distribution">
				</div>


				<p>
					Activity distribution (histogram) shows the total number of unique users (unique ips) grouped on the number of sections views that were logged for each user. 
					The shape of the chart is expected to follow a half-normal distribution (the right half of a normal distribution centered around 0). 
					This assumes a smaller group of highly active users and a larger group of casual viewers.
				</p>
				<p>
					For the following metrics unique user data is seperated into two groups; casual < 16, and active >= 16.


				</p>
				
				<h2>Active user section views compared with casual users</h2>
				<p style="text-align: right;"><label class="active-u">Active users</label> <label class="casual-u" >Casual users</label></p>
				<div id="metrics_tours_div_active" >
					<div id="metrics_tours_div_active_items">

					</div>

					<div id="metrics_tours_div_casual">
					</div>
				</div>



				<div id="chart_12_months_active">
				</div>
				

			<section>

		</main>
	</body>

	<script type="text/javascript">


		var addMetric=function(div, title, result, options){

			options=ObjectAppend_({
				height:240,
				colors:["#00bbde"]
			}, options);

			var chart = new Keen.Dataviz()
			.el(div instanceof HTMLElement?div:document.getElementById(div))
			.height(options.height)
			.title(title)
			.type("metric")
			.colors(options.colors)
			.prepare();

			chart.data(result).render();


		};


		var addChart=function(div, title, result, options){


			options=ObjectAppend_({
				height:340
			}, options);

			var chart = new Keen.Dataviz()
			.el(div instanceof HTMLElement?div:document.getElementById(div))
			.height(options.height)
			.title(title)
			.type("bar")
			.stacked(true)
			.chartOptions({
				 bar: {
			        width: {
			            ratio: 0.9 // this makes bar width 90% of length between ticks
			        }
			    },
			    colors:["#66cdaa"]
			
			});

			if(options.colors){
				chart.colors(options.colors);
			}


			if(options.colorMapping){
				chart.colorMapping(options.colorMapping);
			}


			


			chart.prepare();

			chart.data(result).render();
		}

		addMetric('metric_total', "Total App Section Views", <?php echo json_encode(array('result'=>$q->count())); ?>,{
			colors:['rgb(254, 102, 114)']
		});
		addMetric('metric_ips', "Unique IPs", <?php echo json_encode(array('result'=>$q->countDistinct('ip'))); ?>);



		<?php
		
		$today = strtotime(date('Y-m-d'));

		?>

		addMetric('metric_today', "Today", <?php echo json_encode(array('result'=>$q->countDistinct('ip','WHERE timestamp >= '.$today))); ?>);




		<?php

		$last7days = strtotime(date('Y-m-d', time()-(3600*24*7)));

		?>

		addMetric('metric_7days', "Last 7 days", <?php echo json_encode(array('result'=>$q->countDistinct('ip','WHERE timestamp >= '.$last7days))); ?>);




		<?php

		$thisMonth = strtotime(date('Y-m').'-01');

		?>

		addMetric('metric_month', "This month", <?php echo json_encode(array('result'=>$uniqueThistMonth=$q->countDistinct('ip','WHERE timestamp >= '.$thisMonth))); ?>);




		<?php



		$lastMonth=strtotime(date('Y-m', $thisMonth-(3600*24*10)).'-01');

		?>

		addMetric('metric_lastMonth', "Last month", <?php echo json_encode(array('result'=>$uniqueLastMonth=$q->countDistinct('ip','WHERE timestamp >= '.$lastMonth.' AND timestamp < '.$thisMonth))); ?>);




		<?php


			$nextMonth=strtotime(date('Y-m', $thisMonth+(3600*24*20)).'-01');
			if($thisMonth==$nextMonth){
				$nextMonth=strtotime(date('Y-m', $thisMonth+(3600*24*35)).'-01');
			}



			$end=$lastMonth;
			$results12Months=array(
				array(
					'start'=>date('Y-m', $lastMonth),
					'end'=>date('Y-m', $thisMonth),
 					'unique'=>$uniqueLastMonth,
 					'total'=>$q->count('WHERE timestamp >= '.$thisMonth)
				),
				array(
					'start'=>date('Y-m', $thisMonth),
					'end'=>date('Y-m', $nextMonth),
 					'unique'=>$uniqueThistMonth,
 					'total'=>$q->count('WHERE timestamp >= '.$lastMonth.' AND timestamp < '.$thisMonth)
				)
			);

			for($i=0;$i<10;$i++){


				$start=strtotime(date('Y-m', $end-(3600*24*10)).'-01');

				array_unshift($results12Months, array(

 					'start'=>date('Y-m', $start),
 					'end'=>date('Y-m', $end),
 					'unique'=>$q->countDistinct('ip','WHERE timestamp >= '.$start.' AND timestamp < '.$end),
 					'total'=>$q->count('WHERE timestamp >= '.$start.' AND timestamp < '.$end)
				));

				$end=$start;

			}
		

		?>

		var year=<?php echo json_encode(array_map(function($value){

			return array(
				'value'=>array(

					array(
						'name'=>'Unique Users',
						'result'=>$value['unique']
					),
					array(
						'name'=>'Total Section Views',
						'result'=>$value['total']
					)

				),
				'timeframe'=>array(
					'start'=>$value['start'].'-01',
					'end'=>$value['end'].'-01',
				)

			);

		}, $results12Months), JSON_PRETTY_PRINT);?>


		addChart('chart_12_months', 'Last 12 months', {result:year});



		
		<?php 

			$formatted=[];

			foreach ($q->countDistinctGroups() as $result) {
				$data=json_decode($result['data']);
				if(isset($data->filter->filterTour)){

					$title=$data->filter->filterTour;
					$title=explode(':', $title);
					$title=array_pop($title);
					$title=trim($title);

					if(!array_key_exists($title, $formatted)){
						$formatted[$title]=0;
					}
					$formatted[$title]+=intval($result['count']);

					
				}
			}

			foreach ($formatted as $key => $value) {
				?>
					addMetric(
						document.getElementById('metrics_tours_div').appendChild(new Element('div')), 
						<?php echo json_encode($key); ?>, 
						<?php echo json_encode(array('result'=>$value)); ?>,
						{
							height:200
						});
				<?php
			}
		?>







		<?php 


		$group=function($v){
			return (int) $v;
		};
		$range=function($i){
			return [$i, $i+1];
		};


		$useLogarithmicScale=function($base)use(&$group, &$range){

			$group=function($v)use($base){
				 return (int) log($v, $base); 
			};

			$range=function($i)use($base){
				return [pow($base, $i), pow($base, $i+1)];
			};

		};

		$useLinearScale=function($segmentSize)use(&$group, &$range){

			$group=function($v)use($segmentSize){
				 return (int) $v/$segmentSize;
			};

			$range=function($i)use($segmentSize){
				return [$i*$segmentSize, ($i+1)*$segmentSize];
			};

		};


		$useLogarithmicScale(2);
		//$useLinearScale(10);

		$dist=array();

		$max=0;
		foreach($q->distribution('ip') as $interact){
			$index=$group($interact['count']); // $interact['count']/$groupSize;

			$max=max($index, $max);

			if(isset($dist[$index])){
				$dist[$index]++;
				continue;
			}

			$dist[$index]=1;
		}

		$values=array();
		$ranges=array();

		for($i=0; $i<=$max; $i++){
			if(isset($dist[$i])){
				$values[]=$dist[$i];
			}else{
				$values[]=0;
			}

			$ranges[$i]=$range($i);
		}

		echo json_encode($values);
		echo json_encode($ranges);

		?>


		var distribution=<?php echo json_encode(array_map(function($value, $i)use($ranges){

			return array(
				'result'=>$value,
				'label'=> $ranges[$i][0].' - '.$ranges[$i][1].' Section views',
			);

		}, $values, array_keys($values)), JSON_PRETTY_PRINT);?>


		addChart('chart_distribution', 'Unique user activity distribution (log2)', {result:distribution}, {

			colorMapping:{
			    'Result': function(d){ return '#e0e0e0'; }, // column - color

			  },

			 //colors:["#66cdaa"]
		});




		<?php 

			$formatted=[];

			foreach ($q->countDistinctGroups(' WHERE ip in ('. $q->distributionThreshold('ip', 16, '>=') .') ') as $result) {
				$data=json_decode($result['data']);
				if(isset($data->filter->filterTour)){

					$title=$data->filter->filterTour;
					$title=explode(':', $title);
					$title=array_pop($title);
					$title=trim($title);

					if(!array_key_exists($title, $formatted)){
						$formatted[$title]=0;
					}
					$formatted[$title]+=intval($result['count']);

					
				}
			}

			foreach ($formatted as $key => $value) {
				?>
					addMetric(
						document.getElementById('metrics_tours_div_active_items').appendChild(new Element('div')), 
						<?php echo json_encode($key); ?>, 
						<?php echo json_encode(array('result'=>$value)); ?>,
						{
							height:200,
							colors:["#66cdaa"]
						});
				<?php
			}
		?>



		<?php 

			$formatted=[];

			foreach ($q->countDistinctGroups(' WHERE ip in ('. $q->distributionThreshold('ip', 16, '<') .') ') as $result) {
				$data=json_decode($result['data']);
				if(isset($data->filter->filterTour)){

					$title=$data->filter->filterTour;
					$title=explode(':', $title);
					$title=array_pop($title);
					$title=trim($title);

					if(!array_key_exists($title, $formatted)){
						$formatted[$title]=0;
					}
					$formatted[$title]+=intval($result['count']);

					
				}
			}

			foreach ($formatted as $key => $value) {
				?>
					addMetric(
						document.getElementById('metrics_tours_div_casual').appendChild(new Element('div')), 
						"Casual",
						<?php echo json_encode(array('result'=>$value)); ?>,
						{
							height:200,
							colors:["#f0f0f0"]
						});
				<?php
			}
		?>



		
























		<?php


			$nextMonth=strtotime(date('Y-m', $thisMonth+(3600*24*20)).'-01');
			if($thisMonth==$nextMonth){
				$nextMonth=strtotime(date('Y-m', $thisMonth+(3600*24*35)).'-01');
			}

			$end=$nextMonth;

			$results12Months=array();

			for($i=0;$i<12;$i++){


				$start=strtotime(date('Y-m', $end-(3600*24*10)).'-01');

				array_unshift($results12Months, array(

 					'start'=>date('Y-m', $start),
 					'end'=>date('Y-m', $end),
 					'active'=>$q->countDistinct('ip', 'WHERE timestamp >= '.$start.' AND timestamp < '.$end.' AND ip in ('. $q->distributionThreshold('ip', 16, '>=') .')'),
 					'casual'=>$q->countDistinct('ip', 'WHERE timestamp >= '.$start.' AND timestamp < '.$end.' AND ip in ('. $q->distributionThreshold('ip', 16, '<') .')')
				));

				$end=$start;

			}
		

		?>

		(function(year){


				addChart('chart_12_months_active', 'Active and casual users - last 12 months', {result:year},{
					colors:["#66cdaa", "#e0e0e0"]
				});


		})(<?php echo json_encode(array_map(function($value){

			return array(
				'value'=>array(

					array(
						'name'=>'Active Users',
						'result'=>$value['active']
					),
					array(
						'name'=>'Casual Users',
						'result'=>$value['casual']
					)

				),
				'timeframe'=>array(
					'start'=>$value['start'].'-01',
					'end'=>$value['end'].'-01',
				)

			);

		}, $results12Months), JSON_PRETTY_PRINT);?>);


	











	</script>
</html>
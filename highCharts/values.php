<?php
date_default_timezone_set('GMT');
$conn = pg_connect("dbname=user user=user password=password");
if ($conn) {
				$result = pg_query($conn, "SELECT id, log_dt::timestamp(0) , tmpr , batt , node 
								FROM raw_data 
								WHERE CAST(log_dt As Date) >= (CURRENT_DATE - INTERVAL '1 day')::date");
	if ($result) {
					while ($row = pg_fetch_assoc($result)) {
						echo date("Y-m-d H:i:s" , strtotime($row['log_dt']) ) ."/".$row['tmpr']."/";
		}
	}
	pg_close($conn);
}

?>

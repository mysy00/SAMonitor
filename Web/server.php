<?php
    include 'logic/layout.php';
    PageHeader("server");

    $metrics = json_decode(file_get_contents("http://gateway.markski.ar:42069/api/GetServerMetrics?hours=24&ip_addr=".urlencode($_GET['ip_addr'])), true);

    $playerSet = "";
    $timeSet = "";
    $first = true;

    // API provides data in descendent order, but we'd want to show t
    $metrics = array_reverse($metrics);

    $lowest = 69420;
    $lowest_time = null;
    $highest = -1;
    $highest_time = null;

    $skip = true;

    foreach ($metrics as $instant) {
        $humanTime = strtotime($instant['time']);
        $humanTime = date("H:i", $humanTime);

        if ($instant['players'] > $highest) {
            $highest = $instant['players'];
            $highest_time = $humanTime;
        }
        if ($instant['players'] < $lowest) {
            $lowest = $instant['players'];
            $lowest_time = $humanTime;
        }

        if ($first) {
            $playerSet .= $instant['players'];
            $timeSet .= "'".$humanTime."'";
            $first = false;
        } 
        else {
            $playerSet .= ", ".$instant['players'];
            $timeSet .= ", '".$humanTime."'";
        }
    }

    if (isset($_GET['ip_addr']) && strlen($_GET['ip_addr']) > 0) {
        $server = json_decode(file_get_contents("http://gateway.markski.ar:42069/api/GetServerByIP?ip_addr=".urlencode($_GET['ip_addr'])), true);
    }
    
    if (isset($server)) {
        if ($server['website'] != "Unknown") {
            $website = '<a href="'.$server['website'].'">'.$server['website'].'</a>';
        }
        else {
            $website = "No website specified.";
        }

        $lagcomp = $server['lagComp'] == 1 ? "Enabled" : "Disabled";
        $last_updated = strtotime($server['lastUpdated']);
    }
?>

<div>
    <h2>Server information</h3>
    <?php
        if (!isset($server) || $server['name'] == null) {
            exit("<p>Sorry, there was an error loading this server's information. It may not be in SAMonitor.</p>");
        }
    ?>
    <p><?=$server['name']?></p>
    <div style="display: flex; flex-wrap: wrap; justify-content: start; gap: 1.5rem">
        <div class="innerContent flexBox">
            <h3>Details</h3>
            <table class="serverDetailsTable">
                <tr>
                    <td><b>Players</b></td><td><?=$server['playersOnline']?> / <?=$server['maxPlayers']?></td>
                </tr>
                <tr>
                    <td><b>Gamemode</b></td><td><?=$server['gameMode']?></td>
                </tr>
                <tr>
                    <td><b>Language</b></td><td><?=$server['language']?></td>
                </tr>
                <tr>
                    <td><b>Map</b></td><td><?=$server['mapName']?></td>
                </tr>
                <tr>
                    <td><b>Lag compensation</b></td><td><?=$lagcomp?></td>
                </tr>
                <tr>
                    <td><b>Version</b></td><td><?=$server['version']?></td>
                </tr>
                <tr>
                    <td><b>Website</b></td><td><?=$website?></td>
                </tr>
                <tr>
                    <td><b>SAMPCAC</b></td><td><?=$server['sampCac']?></td>
                </tr>
                <tr>
                    <td><b>Last updated</b></td><td><?=timeSince($last_updated)?> ago</td>
                </tr>
            </table>
            <div style="margin-top: 2rem">
                <div style="float: left; margin-top: 0">
                    <p class="ipAddr" id="ipAddr<?=$server['id']?>"><?=$server['ipAddr']?></p>
                </div>
                <div style="text-align: right; float: right; margin-top: 0">
                    <a href="samp://<?=$server['ipAddr']?>"><button>Connect</button></a><button class="connectButton" onclick="CopyAddress('ipAddr<?=$server['id']?>')">Copy IP</button>
                </div>
            </div>
        </div>
        <div class="innerContent flexBox">
            <h3>Activity graph - Last 24 hours</h3>
            <div style='width: 100% !important'>
                <?php if (count($metrics) > 2) { ?>
                    <canvas id='globalPlayersChart' style='width: 100%'></canvas>
                    <p>The highest player count was <span style='color: green'><?=$highest?></span> at <?=$highest_time?>, and the lowest was <span style='color: red'><?=$lowest?></span> at <?=$lowest_time?></p>

                <?php } else { ?>
                    <p>Not enough data for the activity graph, please check later.</p>
                <?php } ?>
            </div>
            <p>
                <small>
                    Times are UTC 0.
                </small>
            </p>
        </div>
        <div class="innerContent flexBox">
            <h3>Player list</h3>
            <iframe style="width: 100%; height: 15rem; border: 1px solid gray" src="view/bits/playerlist.php?ip_addr=<?=$server['ipAddr']?>&players=<?=$server['playersOnline']?>"></iframe>
        </div>
    </div>
</div>

<script>
    document.title = "SAMonitor - <?=$server['name']?>"
</script>

<script>
    new Chart(document.getElementById('globalPlayersChart'), {
        type: 'line',
        options: {
            responsive: false,
            scales: {
                y: {
                    min: 0
                }
            }
        },
        data: {
            labels: [<?=$timeSet?>],
            datasets: [
                {
                    label: 'Players online',
                    data: [<?=$playerSet?>],
                    borderWidth: 1
                }
            ]
        }
    });
</script>

<?php
    PageBottom();

    function timeSince($time) {
        $time = time() - $time; // to get the time since that moment
        $time = ($time<1)? 1 : $time;
        $tokens = array (
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
        }
    }
?>
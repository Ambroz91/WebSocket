<?php

set_time_limit(0); //zapobiega temu by serwer miał timeout, czyli przekroczenie czasu


require 'class.PHPWebSocket.php'; //wymaga specjalnej klasy która umożliwia działanie WebSocket. Serwer rozpoczyna pracę na samym końcu pliku


function wsOnMessage($clientID, $message, $messageLength, $binary) { //kiedy klient wysyła dane do serwera, ten pobiera jego id, wiadomośc jaką wpisał, długośc wiadomości
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	
	if ($messageLength == 0) { //sprawdza czy długość wpisanego tekstu jest równa 0, jeśli tak to nie wyśle żadnej wiadomości
		$Server->wsClose($clientID);
		return;
	}

	//Jwśli użytkowniok jest sam w pokoju to pokaż mu, ze nie jest samotny i serwer odpisuje na wiadomość 
	if ( sizeof($Server->wsClients) == 1 )
		$Server->wsSend($clientID, "Nikogo innego nie ma w pokoju, ale ja Ciebie słucham --Twój kochający Serwer");
	else
		//wyślij wiadomośc do wszystkich prócz osoby która ją wysłała
		foreach ( $Server->wsClients as $id => $client )
			if ( $id != $clientID )
				$Server->wsSend($id, "Odwiedzajacy $clientID ($ip) powiedział \"$message\""); //Odwiedzajacy ID klienta, jego num,er IP i wiadomosć
}

// kiedy klient się podłaczy
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) podłączył się." );

	//Send a join notice to everyone but the person who joined
	foreach ( $Server->wsClients as $id => $client )
		if ( $id != $clientID )
			$Server->wsSend($id, "Odwiedzający $clientID ($ip) dołączył do pokoju.");
}

// Kiedy klint opuszcza lub rozłącza się z serwerem
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) wylogował się." );

	//Send a user left notice to everyone in the room
	foreach ( $Server->wsClients as $id => $client )
		$Server->wsSend($id, "Odwiedzający $clientID ($ip) opuścił pokuj.");
}

// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('127.0.0.1', 9300);

?>
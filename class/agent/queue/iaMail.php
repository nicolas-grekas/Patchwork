<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends agent_queue_iaCron
{
	protected $queueFolder = 'class/iaMail/queue/';
	protected $dual = 'iaMail';

	protected function queueNext()
	{
		$time = time();
		$sql = "SELECT OID, home FROM queue WHERE sent_time=0 AND send_time AND send_time<={$time} ORDER BY send_time, OID LIMIT 1";
		if ($data = $this->sqlite->query($sql)->fetchObject()) tool_touchUrl::call("{$data->home}queue/iaCron/{$data->OID}/" . $this->getToken();
	}

	protected function doOne($id)
	{
		$sqlite = $this->sqlite;

		$sql = "SELECT archive, data FROM queue WHERE OID={$id}";
		$data = $sqlite->query($sql)->fetchObject();
	
		if (!$data) return;

		$sql = "UPDATE queue SET send_time=0 WHERE OID={$id}";
		$sqlite->query($sql);

		$archive = $data->archive;
		$data = (object) unserialize($data->data);

		$this->restoreSession($data->session);

		isset($data->agent)
			? iaMail_mime::sendAgent($data->headers, $data->agent, $data->argv, $data->options)
			: iaMail_mime::send($data->headers, $data->body, $data->options);

		$sql = $archive
			? "UPDATE queue SET sent_time={$_SERVER['REQUEST_TIME']} WHERE OID={$id}"
			: "DELETE FROM queue WHERE OID={$id}";
		$sqlite->query($sql);
	}
}

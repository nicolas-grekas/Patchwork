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


class extends agent_queue_pTask
{
	protected

	$queueFolder = 'data/queue/iaMail/',
	$dual = 'iaMail';


	protected function queueNext()
	{
		$time = time();
		$sql = "SELECT OID, base, send_time FROM queue WHERE send_time ORDER BY send_time, OID LIMIT 1";
		if ($data = $this->sqlite->query($sql)->fetchObject())
		{
			if ($data->send_time <= $time) tool_touchUrl::call("{$data->base}queue/iaMail/{$data->OID}/" . $this->getToken());
			else pTask::schedule(new pTask(array($this, 'queueNext')), $data->send_time);
		}
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
			? iaMail_mime::sendAgent($data->headers, $data->agent, $data->args, $data->options)
			: iaMail_mime::send($data->headers, $data->body, $data->options);

		$sql = $archive
			? "UPDATE queue SET sent_time={$_SERVER['REQUEST_TIME']} WHERE OID={$id}"
			: "DELETE FROM queue WHERE OID={$id}";
		$sqlite->query($sql);
	}
}

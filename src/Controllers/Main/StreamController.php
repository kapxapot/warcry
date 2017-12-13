<?php

namespace App\Controllers\Main;

class StreamController extends BaseController {
	private $streamsTitle;
	
	public function __construct($container) {
		parent::__construct($container);

		$this->streamsTitle = $this->getSettings('legacy.streams.title');
	}

	public function index($request, $response, $args) {
		$streams = $this->builder->buildSortedStreams();
		$groups = $this->builder->buildStreamGroups($streams);

		$params = $this->buildParams([
			'params' => [
				'title' => $this->streamsTitle,
				'streams' => $streams,
				'groups' => $groups,
			],
		]);
	
		return $this->view->render($response, 'main/streams/index.twig', $params);
	}

	public function item($request, $response, $args) {
		$alias = $args['alias'];

		$row = $this->db->getStreamByAlias($alias);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}
		
		$stream = $this->builder->buildStream($row);

		$params = $this->buildParams([
			'params' => [
				'stream' => $stream,
				'title' => $stream['title'],
				'streams_title' => $this->streamsTitle,
			],
		]);

		return $this->view->render($response, 'main/streams/item.twig', $params);
	}
	
	public function refresh($request, $response, $args) {
		$log = $request->getQueryParam('log', false);
		$notify = $request->getQueryParam('notify', true);

		$rows = $this->db->getStreams();
		
		$streamData = array_map(function($row) use ($notify) {
			return $this->builder->updateStreamData($row, $notify);
		}, $rows);

		$params = [ 
			'data' => $streamData,
			'log' => $log,
		];

		return $this->view->render($response, 'main/streams/refresh.twig', $params);
	}
}

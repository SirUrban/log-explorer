<?php


namespace App\Controller\Api;


use App\Constant\ErrorCodeConstant;
use App\Entity\LogView;
use App\Helper\ColumnHelper;
use App\Helper\StringHelper;
use App\Services\LogView\LogViewServiceInterface;
use App\Services\Stream\StreamServiceInterface;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StreamController extends ApiController
{
    /**
     * @Route("/api/stream/{uuid}/table", methods = "GET")
     * @param LogView|null $logView
     * @param LogViewServiceInterface $logViewService
     * @return JsonResponse
     */
    public function table(?LogView $logView, LogViewServiceInterface $logViewService): JsonResponse
    {
        if (is_null($logView)) {
            $logView = $logViewService->getDefault();
        }
        $columns = $logViewService->getVisibleColumns($logView);
        return $this->responseSuccess([
            'data' => $columns
        ]);
    }

    private function getFilter(Request $request): array
    {
        $options = [];
        if ($request->query->has('from')) {
            $from = $request->query->get('from');
            if (is_numeric($from)) {
                $from = new \DateTime("- {$from} minutes");
            } else {
                $from = new \DateTime($from);
            }
            $options['from'] = $from;
        } else {
            $options['from'] = new \DateTime('- 1 hour');
        }
        if ($request->query->has('to')) {
            $to = $request->query->get('to');
            $to = new \DateTime($to);
            $options['to'] = $to;
        }
        if ($request->query->has('filter')) {
            $filter = $request->query->get('filter');
            $options['filter'] = $filter;
        } else {
            $options['filter'] = false;
        }
        if ($request->query->has('pageIndex')) {
            $options['page'] = intval($request->query->get('pageIndex'));
        }
        if ($request->query->has('pageSize')) {
            $options['limit'] = intval($request->query->get('pageSize'));
        }
        return $options;
    }

    /**
     * @Route("/api/stream/{uuid}/list", methods = "GET")
     * @param LogView|null $logView
     * @param Request $request
     * @param LogViewServiceInterface $logViewService
     * @param StreamServiceInterface $streamService
     * @return JsonResponse
     * @throws \Exception
     */
    public function list(
        ?LogView $logView,
        Request $request,
        LogViewServiceInterface $logViewService,
        StreamServiceInterface $streamService
    ): JsonResponse {
        if (is_null($logView)) {
            $logView = $logViewService->getDefault();

        }
        $options = $this->getFilter($request);
        $columns = $logView->getLogViewColumns();
        if (!empty($columns)) {
            $options['columns'] = $columns;
        }
        try {
            $trackId = StringHelper::random();
            $options['trackId'] = $trackId;
            $total = $streamService->getTotalLogsInRange($logView->getTable(), $options);
            $options['total'] = $total;
            $data = $streamService->getLogsInRange($logView->getTable(), $options);
            $log = $streamService->getLogByTrackId($trackId);
            $queryInfo = [];
            if ($log) {
                $queryInfo = [
                    'queryTime' => $log['query_duration_ms'],
                    'queryMemory' => $log['memory_usage'],
                    'queryReadRows' => $log['read_rows'],
                    'queryReadBytes' => $log['read_bytes'],
                    'queryResultBytes' => $log['result_bytes'],
                ];
            }
        } catch (Exception $e) {
            return $this->responseError([
                'error' => ErrorCodeConstant::ERROR_INVALID_QUERY,
                'data' => [],
                'message' => 'Invalid SQL query',
                'filter' => $options['filter'],
            ]);
        }
        return $this->responseSuccess([
            'data' => $data,
            'itemsCount' => $total,
            'queryInfo' => $queryInfo,
        ]);
    }

    /**
     * @Route("/api/stream/{uuid}/summary", methods = "GET")
     * @param LogView|null $logView
     * @param Request $request
     * @param LogViewServiceInterface $logViewService
     * @param StreamServiceInterface $streamService
     * @return JsonResponse
     */

    public function summary(
        ?LogView $logView,
        Request $request,
        LogViewServiceInterface $logViewService,
        StreamServiceInterface $streamService
    ): JsonResponse {
        if (is_null($logView)) {
            $logView = $logViewService->getDefault();
        }
        $columns = $logView->getSummary();
        $options = $this->getFilter($request);
        $widgets = [];
        foreach ($columns as $column) {
            try {
                $widget = [
                    'name' => $column,
                    'title' => ColumnHelper::titleFromName($column)
                ];
                $widget['data'] = $streamService->getLogSummaryInRange($logView->getTable(), $widget['name'], $options);
                $widgets[] = $widget;
            } catch (Exception $e) {
                return $this->responseError([
                    'error' => ErrorCodeConstant::ERROR_INVALID_QUERY,
                    'data' => [],
                    'message' => 'Invalid SQL query',
                    'filter' => $options['filter'],
                ]);
            }
        }
        return $this->responseSuccess([
            'data' => $widgets,
        ]);
    }

    /**
     * @Route("/api/stream/{uuid}/graph", methods = "GET")
     * @param LogView|null $logView
     * @param Request $request
     * @param LogViewServiceInterface $logViewService
     * @param StreamServiceInterface $streamService
     * @return JsonResponse
     */
    public function graph(
        ?LogView $logView,
        Request $request,
        LogViewServiceInterface $logViewService,
        StreamServiceInterface $streamService
    ): JsonResponse {
        if (is_null($logView)) {
            $logView = $logViewService->getDefault();
        }
        $options = $this->getFilter($request);
        $graph = $logView->getGraph();
        $graphOffset = $streamService->getGraphOffsetInSeconds($options['from'], $options['to'] ?? new \DateTime(), $graph->getMaxPoint());
        $data = [];
        foreach ($graph->getLines() as $item) {
            try {
                $line = [
                    'label' => $item->getTitle(),
                    'color' => $item->getColor(),
                    'data' => $streamService->getLogGraphInRange($logView->getTable(), $item, $graphOffset, $options),
                ];
                $data[] = $line;
            } catch (Exception $e) {
                return $this->responseError([
                    'error' => ErrorCodeConstant::ERROR_INVALID_QUERY,
                    'data' => [],
                    'message' => 'Invalid SQL query',
                    'filter' => $options['filter'],
                ]);
            }
        }
        return $this->responseSuccess([
            'data' => $data,
        ]);
    }
}

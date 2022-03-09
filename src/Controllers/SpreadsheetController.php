<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;
use ElliotGhorbani\LaravelSpreadsheet\Repositories\SpreadsheetRepository;
use ElliotGhorbani\LaravelSpreadsheet\Requests\SpreadsheetRequest;
use ElliotGhorbani\LaravelSpreadsheet\Requests\TableRequest;
use ElliotGhorbani\LaravelSpreadsheet\Resources\SpreadsheetResource;
use ElliotGhorbani\LaravelSpreadsheet\Services\Export;

class SpreadsheetController extends Controller
{
    /**
     * @var SpreadsheetRepository
     */
    private SpreadsheetRepository $repository;

    /**
     * @param SpreadsheetRepository $spreadsheetRepository SpreadsheetRepository.
     */
    public function __construct(SpreadsheetRepository $spreadsheetRepository)
    {
        $this->repository = $spreadsheetRepository;
    }

    /**
     * @param Request           $request Request.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SpreadsheetResource::collection(
            Spreadsheet::filter()->paginate($this->getPageSize($request))
        );
    }

    /**
     * @param SpreadsheetRequest $request Request.
     *
     * @return JsonResponse|SpreadsheetResource
     */
    public function store(SpreadsheetRequest $request): JsonResponse|SpreadsheetResource
    {
         $result = $this->repository->store($request->validated());

        if (isset($result['error'])) {
            return $this->getResponse(['message' => $result['message']], $result['status']);
        }

        return new SpreadsheetResource($result);
    }

    /**
     * @param Spreadsheet $spreadsheet Spreadsheet
     *
     * @return SpreadsheetResource
     */
    public function show(Spreadsheet $spreadsheet): SpreadsheetResource
    {
        return new SpreadsheetResource($spreadsheet);
    }

    /**
     * @param SpreadsheetRequest $request     Request.
     * @param Spreadsheet        $spreadsheet export.
     *
     * @return JsonResponse|SpreadsheetResource
     */
    public function update(SpreadsheetRequest $request, Spreadsheet $spreadsheet): JsonResponse|SpreadsheetResource
    {
        $result = $this->repository->update(
            $spreadsheet,
            $request->validated()
        );

        if (isset($result['error'])) {
            return $this->getResponse(['message' => $result['message']], $result['status']);
        }

        return new SpreadsheetResource($result);
    }

    /**
     * @param Spreadsheet $spreadsheet Spreadsheet
     *
     * @return JsonResponse
     */
    public function destroy(Spreadsheet $spreadsheet): JsonResponse
    {
        try {
            $this->repository->delete($spreadsheet);

            return $this->getResponse([], Response::HTTP_NO_CONTENT);
        } catch (\Exception $exception) {
            Log::error('Delete Spreadsheet Export Error : ' . $exception->getMessage());

            return $this->getResponse(
                ['message' => __(
                    'spreadsheet::exceptions.system_cant_action',
                    ['action' => __('spreadsheet::exceptions.delete'), 'modelName' => __('spreadsheet::exceptions.spreadsheet')]
                )],
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * @param Request $request Request
     *
     * @return int
     */
    protected function getPageSize(Request $request): int
    {
        $pageSize = 10;
        if ($request->filled('per_page')) {
            $pageSize = (int)$request->get('per_page');
        }

        return $pageSize;
    }

    /**
     * get all tables from database
     *
     * @return JsonResource
     */
    public function getTables(): JsonResource
    {
        $allTables = $this->repository->getAllTables();

        return JsonResource::make($allTables);
    }

    /**
     * get all columns of the specified table
     *
     * @param TableRequest $request request
     *
     * @return JsonResource
     */
    public function getColumns(TableRequest $request): JsonResource
    {
        return JsonResource::make(
            $this->repository->getColumns($request->get(Spreadsheet::TABLE_NAME)),
        );
    }

    /**
     * get all columns of the specified table
     *
     * @param TableRequest $request request
     *
     * @return JsonResource
     */
    public function getFilterColumns(TableRequest $request): JsonResource
    {
        return JsonResource::make(
            $this->repository->getFilterColumns($request->get(Spreadsheet::TABLE_NAME)),
        );
    }

    /**
     * export spreadsheet file
     *
     * @param Spreadsheet $spreadsheet spreadsheet
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function export(Spreadsheet $spreadsheet)
    {
        try {
            $export = new Export(
                $spreadsheet[Spreadsheet::TABLE_NAME],
                $spreadsheet->{Spreadsheet::EXPORT_DATA}[Spreadsheet::EXPORT_DATA_COLUMNS],
                $spreadsheet->{Spreadsheet::EXPORT_DATA}[Spreadsheet::EXPORT_DATA_FILTERS],
            );

            if (request()->get('limit')) {
                $export->setLimitOffset(request()->get('limit'), request()->get('offset'));
            }

            return $export->download();
        } catch (\Exception $exception) {
            Log::error('Export Spreadsheet Error : ' . $exception->getMessage());

            return $this->getResponse(
                ['message' => __('spreadsheet::error.spreadsheet_export')],
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * download spreadsheet file
     *
     * @param Spreadsheet $spreadsheet spreadsheet
     */
    public function import(Spreadsheet $spreadsheet)
    {
        try {
            //TODO: Implement import csv
        } catch (\Exception $exception) {
            Log::error('Import Spreadsheet Error : ' . $exception->getMessage());

            return $this->getResponse(
                ['message' => __('spreadsheet::exception.spreadsheet_import')],
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * @param array|null $content    Content.
     * @param integer    $statusCode Status Code.
     * @param array|null $heathers   Headers.
     *
     * @return JsonResponse
     */
    protected function getResponse(
        ?array $content = null,
        int $statusCode = Response::HTTP_OK,
        ?array $heathers = []
    ): JsonResponse {
        if (
            isset($content['message']) &&
            !in_array(env('APP_ENV'), ['local', 'development', 'testing'])
        ) {
            unset($content['message']);
        }

        return response()->json(['data' => $content], $statusCode, $heathers);
    }
}

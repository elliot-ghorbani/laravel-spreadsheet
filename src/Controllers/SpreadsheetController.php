<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Controllers;

use ElliotGhorbani\LaravelSpreadsheet\Exceptions\ExportSpreadsheetException;
use ElliotGhorbani\LaravelSpreadsheet\Exceptions\ImportSpreadsheetException;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;
use ElliotGhorbani\LaravelSpreadsheet\Repositories\SpreadsheetRepository;
use ElliotGhorbani\LaravelSpreadsheet\Requests\SpreadsheetRequest;
use ElliotGhorbani\LaravelSpreadsheet\Requests\TableRequest;
use ElliotGhorbani\LaravelSpreadsheet\Resources\SpreadsheetResource;
use ElliotGhorbani\LaravelSpreadsheet\Services\Export;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

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
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return SpreadsheetResource::collection(
            Spreadsheet::query()->paginate()
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
        $result = $this->repository->update($spreadsheet, $request->validated());

        return new SpreadsheetResource($result);
    }

    /**
     * @param Spreadsheet $spreadsheet Spreadsheet
     *
     * @return JsonResponse
     */
    public function destroy(Spreadsheet $spreadsheet): JsonResponse
    {
        $this->repository->delete($spreadsheet);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * get all tables from database
     *
     * @return JsonResource
     */
    public function getTables(): JsonResource
    {
        return JsonResource::make(
            $this->repository->getAllTables()
        );
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
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws ExportSpreadsheetException
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
            Log::error('LaravelSpreadsheet: ' . $exception->getMessage());

            throw new ExportSpreadsheetException();
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
            Log::error('LaravelSpreadsheet: ' . $exception->getMessage());

            throw new ImportSpreadsheetException();
        }
    }
}

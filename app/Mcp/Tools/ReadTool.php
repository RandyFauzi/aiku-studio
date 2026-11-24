<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[IsReadOnly]
#[IsIdempotent]
abstract class ReadTool extends Tool
{
    protected const ABILITY = 'context';

    public function shouldRegister(): bool
    {
        return app(McpAccess::class)->allows(static::ABILITY);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return McpToolOutput::envelope($schema, $this->dataSchema($schema));
    }

    /**
     * Define the payload schema inside the standard `data` field.
     *
     * @return array<string, mixed>
     */
    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->nullable(),
        ];
    }

    abstract protected function execute(
        Request $request,
        McpAccess $access,
        McpReadQueries $queries,
    ): array;

    public function handle(
        Request $request,
        McpAccess $access,
        McpReadQueries $queries,
    ): Response|ResponseFactory {
        $user = $access->currentUser();
        if (! $user || ! $access->allows(static::ABILITY, $user)) {
            return Response::error('Anda tidak memiliki izin untuk mengakses data ini.');
        }

        try {
            $data = $this->execute($request, $access, $queries);

            return Response::structured([
                'ok' => true,
                'trace_id' => $access->traceId(),
                'observed_at' => now($queries->timezone())->toIso8601String(),
                'data' => $data,
            ]);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? 'Parameter permintaan tidak valid.';

            return Response::error((string) $firstMessage);
        } catch (HttpException $e) {
            return Response::error($e->getMessage() ?: 'Akses ke data ini ditolak.');
        } catch (Throwable $e) {
            report($e);

            return Response::error('Terjadi kesalahan saat memproses permintaan data.');
        }
    }
}

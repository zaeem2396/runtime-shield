<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Performance;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Performance\NullSignalPipeline;
use Symfony\Component\HttpFoundation\Response;

final class NullSignalPipelineTest extends TestCase
{
    private NullSignalPipeline $pipeline;

    public function test_collect_request_returns_void_without_exception(): void
    {
        $this->pipeline->collectRequest(Request::create('/'));
        $this->addToAssertionCount(1);
    }

    public function test_assemble_always_returns_null(): void
    {
        $result = $this->pipeline->assemble(new Response(), 0.0);
        $this->assertNull($result);
    }

    public function test_reset_returns_void_without_exception(): void
    {
        $this->pipeline->reset();
        $this->addToAssertionCount(1);
    }

    public function test_assemble_returns_null_regardless_of_start_time(): void
    {
        $this->pipeline->collectRequest(Request::create('/test'));
        $result = $this->pipeline->assemble(new Response(), 1234567.0);
        $this->assertNull($result);
    }

    public function test_can_call_methods_multiple_times(): void
    {
        $request = Request::create('/');

        for ($i = 0; $i < 5; $i++) {
            $this->pipeline->collectRequest($request);
            $this->assertNull($this->pipeline->assemble(new Response(), 0.0));
            $this->pipeline->reset();
        }

        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        $this->pipeline = new NullSignalPipeline();
    }
}

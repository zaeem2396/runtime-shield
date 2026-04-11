<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Sampling;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Sampling\AlwaysSampler;
use RuntimeShield\Core\Sampling\NeverSampler;
use RuntimeShield\Core\Sampling\PercentageSampler;
use RuntimeShield\Core\Sampling\SamplerChain;

final class SamplerChainTest extends TestCase
{
    public function test_should_sample_true_when_all_samplers_agree(): void
    {
        $chain = new SamplerChain([new AlwaysSampler(), new AlwaysSampler()]);
        $this->assertTrue($chain->shouldSample());
    }

    public function test_should_sample_false_when_any_sampler_rejects(): void
    {
        $chain = new SamplerChain([new AlwaysSampler(), new NeverSampler()]);
        $this->assertFalse($chain->shouldSample());
    }

    public function test_should_sample_false_when_first_sampler_rejects(): void
    {
        $chain = new SamplerChain([new NeverSampler(), new AlwaysSampler()]);
        $this->assertFalse($chain->shouldSample());
    }

    public function test_effective_rate_is_product_of_all_rates(): void
    {
        $chain = new SamplerChain([
            new PercentageSampler(0.5),
            new PercentageSampler(0.8),
        ]);

        $this->assertEqualsWithDelta(0.4, $chain->rate(), 0.0001);
    }

    public function test_effective_rate_with_always_sampler_is_unchanged(): void
    {
        $chain = new SamplerChain([new AlwaysSampler(), new PercentageSampler(0.5)]);
        $this->assertEqualsWithDelta(0.5, $chain->rate(), 0.0001);
    }

    public function test_effective_rate_with_never_sampler_is_zero(): void
    {
        $chain = new SamplerChain([new AlwaysSampler(), new NeverSampler()]);
        $this->assertSame(0.0, $chain->rate());
    }

    public function test_count_returns_number_of_samplers(): void
    {
        $chain = new SamplerChain([new AlwaysSampler(), new NeverSampler(), new AlwaysSampler()]);
        $this->assertSame(3, $chain->count());
    }

    public function test_samplers_returns_all_samplers(): void
    {
        $a = new AlwaysSampler();
        $n = new NeverSampler();
        $chain = new SamplerChain([$a, $n]);

        $this->assertCount(2, $chain->samplers());
    }

    public function test_is_empty_false_when_samplers_present(): void
    {
        $chain = new SamplerChain([new AlwaysSampler()]);
        $this->assertFalse($chain->isEmpty());
    }

    public function test_is_empty_true_when_no_samplers(): void
    {
        $chain = new SamplerChain([]);
        $this->assertTrue($chain->isEmpty());
    }

    public function test_empty_chain_always_samples(): void
    {
        $chain = new SamplerChain([]);
        $this->assertTrue($chain->shouldSample());
    }
}

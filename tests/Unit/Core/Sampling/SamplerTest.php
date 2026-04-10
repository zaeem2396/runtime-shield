<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Sampling;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Sampling\AlwaysSampler;
use RuntimeShield\Core\Sampling\NeverSampler;
use RuntimeShield\Core\Sampling\PercentageSampler;
use RuntimeShield\Core\Sampling\SamplerFactory;

final class SamplerTest extends TestCase
{
    // --- AlwaysSampler ---

    public function test_always_sampler_returns_true(): void
    {
        $sampler = new AlwaysSampler();

        $this->assertTrue($sampler->shouldSample());
    }

    public function test_always_sampler_rate_is_one(): void
    {
        $this->assertSame(1.0, (new AlwaysSampler())->rate());
    }

    // --- NeverSampler ---

    public function test_never_sampler_returns_false(): void
    {
        $sampler = new NeverSampler();

        $this->assertFalse($sampler->shouldSample());
    }

    public function test_never_sampler_rate_is_zero(): void
    {
        $this->assertSame(0.0, (new NeverSampler())->rate());
    }

    // --- PercentageSampler ---

    public function test_percentage_sampler_with_zero_rate_never_samples(): void
    {
        $sampler = new PercentageSampler(0.0);

        $this->assertFalse($sampler->shouldSample());
    }

    public function test_percentage_sampler_with_full_rate_always_samples(): void
    {
        $sampler = new PercentageSampler(1.0);

        $this->assertTrue($sampler->shouldSample());
    }

    public function test_percentage_sampler_exposes_rate(): void
    {
        $sampler = new PercentageSampler(0.75);

        $this->assertSame(0.75, $sampler->rate());
    }

    public function test_percentage_sampler_with_negative_rate_never_samples(): void
    {
        $sampler = new PercentageSampler(-0.5);

        $this->assertFalse($sampler->shouldSample());
    }

    public function test_percentage_sampler_with_rate_above_one_always_samples(): void
    {
        $sampler = new PercentageSampler(1.5);

        $this->assertTrue($sampler->shouldSample());
    }

    // --- SamplerFactory ---

    public function test_factory_returns_never_sampler_for_zero_rate(): void
    {
        $sampler = SamplerFactory::fromRate(0.0);

        $this->assertInstanceOf(NeverSampler::class, $sampler);
    }

    public function test_factory_returns_never_sampler_for_negative_rate(): void
    {
        $sampler = SamplerFactory::fromRate(-1.0);

        $this->assertInstanceOf(NeverSampler::class, $sampler);
    }

    public function test_factory_returns_always_sampler_for_full_rate(): void
    {
        $sampler = SamplerFactory::fromRate(1.0);

        $this->assertInstanceOf(AlwaysSampler::class, $sampler);
    }

    public function test_factory_returns_always_sampler_for_rate_above_one(): void
    {
        $sampler = SamplerFactory::fromRate(2.0);

        $this->assertInstanceOf(AlwaysSampler::class, $sampler);
    }

    public function test_factory_returns_percentage_sampler_for_fractional_rate(): void
    {
        $sampler = SamplerFactory::fromRate(0.5);

        $this->assertInstanceOf(PercentageSampler::class, $sampler);
        $this->assertSame(0.5, $sampler->rate());
    }
}

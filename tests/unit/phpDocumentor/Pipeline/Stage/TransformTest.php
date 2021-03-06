<?php

declare(strict_types=1);

namespace phpDocumentor\Pipeline\Stage;

use phpDocumentor\Descriptor\ProjectDescriptor;
use phpDocumentor\Descriptor\ProjectDescriptorBuilder;
use phpDocumentor\Dsn;
use phpDocumentor\Reflection\DocBlock\ExampleFinder;
use phpDocumentor\Transformer\Template\Collection;
use phpDocumentor\Transformer\Transformer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\ProphecyMock;
use Psr\Log\LoggerInterface;
use function getcwd;
use const DIRECTORY_SEPARATOR;

/**
 * @coversDefaultClass \phpDocumentor\Pipeline\Stage\Transform
 * @covers ::__construct
 */
final class TransformTest extends TestCase
{
    use ProphecyTrait;

    /** @var ProjectDescriptorBuilder|ProphecyMock */
    private $projectDescriptorBuilder;

    /** @var Transformer|ProphecyMock */
    private $transformer;

    /** @var LoggerInterface|ProphecyMock */
    private $logger;

    /** @var ExampleFinder|ProphecyMock */
    private $exampleFinder;

    /** @var Compile */
    private $transform;

    public function setUp() : void
    {
        $projectDescriptor = new ProjectDescriptor('test');
        $this->projectDescriptorBuilder = $this->prophesize(ProjectDescriptorBuilder::class);
        $this->projectDescriptorBuilder->getProjectDescriptor()->willReturn($projectDescriptor);
        $this->transformer              = $this->prophesize(Transformer::class);
        $this->logger                   = $this->prophesize(LoggerInterface::class);
        $this->exampleFinder            = $this->prophesize(ExampleFinder::class);
        $this->transformer->execute($projectDescriptor)->shouldBeCalled();

        $this->transform = new Transform(
            $this->transformer->reveal(),
            $this->logger->reveal(),
            $this->exampleFinder->reveal()
        );
    }

    /**
     * @covers ::__invoke
     * @covers ::setTargetLocationBasedOnDsn
     */
    public function test_if_target_location_for_output_is_set_with_a_relative_path() : void
    {
        $config = $this->givenAnExampleConfigWithDsnAndTemplates('.');

        $payload = new Payload($config, $this->projectDescriptorBuilder->reveal());

        $this->transformer->setTarget(getcwd() . DIRECTORY_SEPARATOR . '.')->shouldBeCalled();

        ($this->transform)($payload);
    }

    /**
     * @covers ::__invoke
     * @covers ::setTargetLocationBasedOnDsn
     */
    public function test_if_target_location_for_output_is_set_with_an_absolute_path() : void
    {
        $config = $this->givenAnExampleConfigWithDsnAndTemplates('file:///my/absolute/folder');
        $this->projectDescriptorBuilder->getProjectDescriptor()->willReturn(new ProjectDescriptor('test'));
        $payload = new Payload($config, $this->projectDescriptorBuilder->reveal());

        $this->transformer->setTarget('/my/absolute/folder')->shouldBeCalled();

        ($this->transform)($payload);
    }

    /**
     * @covers ::__invoke
     * @covers ::loadTemplatesBasedOnNames
     */
    public function test_loading_templates_with_a_given_set_of_template_names() : void
    {
        $config = $this->givenAnExampleConfigWithDsnAndTemplates(
            'file://.',
            [
                ['name' => 'template1'],
                ['name' => 'template2'],
            ]
        );

        $payload = new Payload($config, $this->projectDescriptorBuilder->reveal());

        $this->transformer->setTarget(Argument::any());

        $templateCollection = $this->prophesize(Collection::class);
        $templateCollection->load($this->transformer, 'template1')->shouldBeCalled();
        $templateCollection->load($this->transformer, 'template2')->shouldBeCalled();

        $this->transformer->getTemplates()->willReturn($templateCollection->reveal());

        ($this->transform)($payload);
    }

    /**
     * @covers ::__invoke
     */
    public function test_transforming_the_project_will_invoke_all_compiler_passes() : void
    {
        $config            = $this->givenAnExampleConfigWithDsnAndTemplates('file://.');
        $payload           = new Payload($config, $this->projectDescriptorBuilder->reveal());

        $this->transformer->setTarget(Argument::any());

        ($this->transform)($payload);
    }

    private function givenAnExampleConfigWithDsnAndTemplates(string $dsn, array $templates = []) : array
    {
        return [
            'phpdocumentor' => [
                'paths' => [
                    'output' => Dsn::createFromString($dsn),
                ],
                'templates' => $templates,
            ],
        ];
    }
}

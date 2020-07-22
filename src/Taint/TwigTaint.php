<?php

declare(strict_types=1);

namespace Psalm\SymfonyPsalmPlugin\Taint;


use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\SymfonyPsalmPlugin\Test\TwigBridge;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use RuntimeException;
use Twig\Environment;
use Twig\Template;

class TwigTaint implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return [
            Environment::class,
        ];
    }

    public static function getMethodReturnType(StatementsSource $source, string $fq_classlike_name, string $method_name_lowercase, array $call_args, Context $context, CodeLocation $code_location, array $template_type_parameters = null, string $called_fq_classlike_name = null, string $called_method_name_lowercase = null)
    {
        if(!$source instanceof StatementsAnalyzer) {
            throw new RuntimeException(sprintf('The %s::%s hook can only be called using a %s.', __CLASS__, __METHOD__, StatementsAnalyzer::class));
        }

        if ($method_name_lowercase !== 'render') {
            return;
        }

        $fake_method_call = new MethodCall(
            new Variable(
                '__fake_twig_env_var__'
            ),
            new Identifier(
                'doDisplay'
            ),
            [$call_args[1]]
        );

        $firstArgument = $call_args[0]->value;
        if(!$firstArgument instanceof String_) {
            return;
        }

        $template = self::getTemplate($firstArgument->value);
        $context->vars_in_scope['$__fake_twig_env_var__'] = new Union([
            new TNamedObject(get_class($template))
        ]);

        MethodCallAnalyzer::analyze(
            $source,
            $fake_method_call,
            $context
        );
    }

    private static function getTemplate($templateName): Template
    {
        $rootDir = __DIR__.'/../../tests/_run';
        $twigEnvironment = TwigBridge::getEnvironment($rootDir, $rootDir.'/cache');

        $template = $twigEnvironment->load($templateName);
        return $template->unwrap();
    }

    /**
     * This method should be called by some hook happening before the ProjectAnalyzer scans the files
     * Here in the test, as the template cache directory is located under the root directory, it will be analysed, but it will no longer be the case in a real world project
     */
    public function beforeCodebaseIsPopulated(Codebase $codebase)
    {
        // Add some logic to find the twig cache directory (maybe simply using config ?)
        // foreach twigCompiledClass : $codebase->addFilesToAnalyze();
    }
}
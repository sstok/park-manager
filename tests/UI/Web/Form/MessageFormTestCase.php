<?php

declare(strict_types=1);

namespace ParkManager\Tests\UI\Web\Form;

use Closure;
use Psr\Container\ContainerInterface;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Service\ServiceLocatorTrait;
use function ctype_digit;
use function explode;
use function is_callable;

abstract class MessageFormTestCase extends TypeTestCase
{
    protected $dispatchedCommand;

    /** @var callable|null */
    protected $commandHandler;

    protected function getMessageType(): MessageFormType
    {
        $handlers = [
            static::getCommandName() => [
                'handler' => function (object $command) {
                    if (is_callable($this->commandHandler)) {
                        ($this->commandHandler)($command);
                        $this->dispatchedCommand = $command;

                        return;
                    }

                    $this->fail('The "commandHandler" property must be set with valid callable.');
                },
            ],
        ];
        $messageBus = new MessageBus([new HandleMessageMiddleware(new HandlersLocator($handlers), false)]);

        return new MessageFormType($messageBus, new IdentityTranslator());
    }

    abstract protected static function getCommandName(): string;

    /**
     * @param array<string|null,FormError[]> $expectedErrors
     */
    protected function assertFormHasErrors(FormInterface $form, iterable $expectedErrors): void
    {
        self::assertFalse($form->isValid());
        self::assertNull($form->getTransformationFailure());
        self::assertNull($this->dispatchedCommand);

        foreach ($expectedErrors as $formPath => $formErrors) {
            $formPath    = (string) $formPath;
            $currentForm = $form;

            if ($formPath !== '' && ! ctype_digit($formPath)) {
                foreach (explode('.', $formPath) as $child) {
                    $currentForm = $currentForm->get($child);
                }
            }

            self::assertThat($currentForm->getErrors(), new IsFormErrorsEqual($formErrors));
        }
    }
}

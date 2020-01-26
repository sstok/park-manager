<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use ParkManager\UI\Web\Menu\Event\ClientMainMenuBuilderEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ClientMainMenu
{
    /** @var FactoryInterface */
    private $factory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(FactoryInterface $factory, EventDispatcherInterface $eventDispatcher)
    {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $this->eventDispatcher->dispatch(new ClientMainMenuBuilderEvent($this->factory, $menu));

        return $menu;
    }
}

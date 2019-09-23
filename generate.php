<?php
declare(strict_types=1);

namespace Prooph\EventStoreClient;

use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use Prooph\EventStore\Async\EventAppearedOnCatchupSubscription;
use Prooph\EventStore\Async\EventAppearedOnSubscription;
use Prooph\EventStore\Async\EventStoreCatchUpSubscription;
use Prooph\EventStore\CatchUpSubscriptionSettings;
use Prooph\EventStore\EndPoint;
use Prooph\EventStore\EventData;
use Prooph\EventStore\EventId;
use Prooph\EventStore\EventStoreSubscription;
use Prooph\EventStore\ExpectedVersion;
use Prooph\EventStore\ResolvedEvent;
use Prooph\EventStore\SubscriptionDropped;
use Prooph\EventStore\SubscriptionDropReason;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreClient\Internal\VolatileEventStoreSubscription;
use Throwable;

require_once './vendor/autoload.php';

Loop::run(function () {
	$connection = EventStoreConnectionFactory::createFromEndPoint(
		new EndPoint('127.0.0.1', 1113)
	);
	$connection->onConnected(function (): void {
		echo 'connected' . PHP_EOL;
	});
	$connection->onClosed(function (): void {
		echo 'connection closed' . PHP_EOL;
	});
	$connection->connectAsync();

	$connection->appendToStreamAsync('Foo-' . (string)time(), ExpectedVersion::ANY,
		[
			new EventData(
				EventId::generate(),
				'Foo.created',
				true,
				json_encode(['test' => 'test']),
				json_encode(['test' => 'test'])
			),
			new EventData(
				EventId::generate(),
				'Foo.somethingElse',
				true,
				json_encode(['test' => 'test2']),
				json_encode(['test' => 'test2'])
			)
		]
	);
});

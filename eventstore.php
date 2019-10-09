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
	yield $connection->connectAsync();

	// Create a few events
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

	$settings = new CatchUpSubscriptionSettings(
		50,
		50,
		true,
		true,
		'$category-Foo'
	);

	$subscription = yield $connection->subscribeToStreamFromAsync(
		'$ce-Foo',
		0,
		$settings,
		new class() implements EventAppearedOnCatchupSubscription {
			public function __invoke(
				EventStoreCatchUpSubscription $subscription,
				ResolvedEvent $resolvedEvent): Promise
			{
				echo 'incoming event: ' . $resolvedEvent->event()->eventNumber() . '@' . $resolvedEvent->originalStreamName() . PHP_EOL;
				echo 'type: ' . $resolvedEvent->event()->eventType() . PHP_EOL;
				echo 'data: ' . $resolvedEvent->event()->data() . PHP_EOL;

				// var_dump($resolvedEvent->event());

				return new Success();
			}
		}
	);
});

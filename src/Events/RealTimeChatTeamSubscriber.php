<?php


namespace App\Events;


use ApiPlatform\Core\EventListener\EventPriorities;


use App\Entity\ChatTeam;
use Pusher\Pusher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

final  class RealTimeChatTeamSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security){

    }
    /**
     * @return array
     */
    public static function getSubscribedEvents():array
    {
        return [
            KernelEvents::VIEW => ['RealTimeChatTeamSubscriber', EventPriorities::POST_WRITE],
        ];
    }

    /**
     * @param ViewEvent $event
     */
    public function RealTimeChatTeamSubscriber(ViewEvent $event)
    {
        $chatTeam = $event->getControllerResult();

        $method = $event->getRequest()->getMethod();
        if (!$chatTeam instanceof ChatTeam) {
            return;
        }

        switch ($method) {
            case Request::METHOD_POST:
                $this->RequestToRealTimeChat($chatTeam->getMessage(), $chatTeam->getUser()->getCode(), $chatTeam->getCreatedAt(), "POST", $chatTeam->getTeam()->getSlug(), $chatTeam->getToken());
                break;
            case Request::METHOD_PATCH || Request::METHOD_PUT:
                $this->RequestToRealTimeChat($chatTeam->getMessage(), $chatTeam->getUser()->getCode(), $chatTeam->getCreatedAt(), "EDIT", $chatTeam->getTeam()->getSlug(), $chatTeam->getToken());
                break;
            case Request::METHOD_DELETE:
                $this->RequestToRealTimeChat("","",null,"DELETE",$chatTeam->getTeam()->getSlug(),$chatTeam->getToken());

        }
    }
        private function RequestToRealTimeChat(string $message = null, string $user = null, \DateTime $createdAt = null, string $satus, string $teamToken = null, string $id)
        {
            $options = array(
                'cluster' => 'eu',
                'useTLS' => true
            );
            $pusher = new Pusher(
              "","","",
                $options
            );

            if ($satus === "POST" || $satus === "EDIT") {
                $data['message'] = $message;
                $data["sender"] = $user;
                $data["createdAt"] = $createdAt;
                $data["status"] = $satus;
                $data["idChat"] = $id;
            } elseif ($satus === "DELETE") {
                $data["status"] = $satus;
                $data["idChat"] = $id;
            }


            $pusher->trigger("channel-chatteam-$teamToken", 'my-event', $data);
        }


}

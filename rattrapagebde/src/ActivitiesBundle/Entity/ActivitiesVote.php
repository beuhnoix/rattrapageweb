<?php

namespace ActivitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActivitiesVote
 *
 * @ORM\Table(name="activities_votes")
 * @ORM\Entity(repositoryClass="ActivitiesBundle\Repository\ActivitiesVoteRepository")
 */
class ActivitiesVote
{

    /**
    * @ORM\ManyToOne(targetEntity="ActivitiesBundle\Entity\ActivityIdea")
    * @ORM\JoinColumn(nullable=false)
    */
    private $activity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="vote", type="integer")
     */
    private $vote;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set vote
     *
     * @param integer $vote
     * @return ActivitiesVote
     */
    public function setVote($vote)
    {
        $this->vote = $vote;

        return $this;
    }

    /**
     * Get vote
     *
     * @return integer 
     */
    public function getVote()
    {
        return $this->vote;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return ActivitiesVote
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set activity
     *
     * @param \ActivitiesBundle\Entity\ActivityIdea $activity
     * @return Activity
     */
    public function setActivity(\ActivitiesBundle\Entity\ActivityIdea $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \ActivitiesBundle\Entity\ActivityIdea
     */
    public function getActivity()
    {
        return $this->activity;
    }
}

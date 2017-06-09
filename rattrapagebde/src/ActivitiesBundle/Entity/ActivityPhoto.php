<?php

namespace ActivitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActivityPhoto
 *
 * @ORM\Table(name="activities_photos")
 * @ORM\Entity(repositoryClass="ActivitiesBundle\Repository\ActivityPhotoRepository")
 */
class ActivityPhoto
{
    /**
     * @ORM\ManyToOne(targetEntity="ActivitiesBundle\Entity\Activity")
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
     * @var string
     *
     * @ORM\Column(name="photo", type="string", length=255)
     */
    private $photo;

    /**
     * @var int
     *
     * @ORM\Column(name="love", type="integer")
     */
    private $love;


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
     * Set photo
     *
     * @param string $photo
     * @return ActivityPhoto
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return string 
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set love
     *
     * @param integer $love
     * @return ActivityPhoto
     */
    public function setLove($love)
    {
        $this->love = $love;

        return $this;
    }

    /**
     * Get love
     *
     * @return integer 
     */
    public function getLove()
    {
        return $this->love;
    }

    /**
     * Set activity
     *
     * @param \ActivitiesBundle\Entity\Activity $activity
     * @return Activity
     */
    public function setActivity(\ActivitiesBundle\Entity\Activity $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \ActivitiesBundle\Entity\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }
}

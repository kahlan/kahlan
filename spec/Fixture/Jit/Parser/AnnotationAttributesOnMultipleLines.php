<?php

class Entity
{
    #[OneToMany(
        targetEntity: "Phonenumber",
        mappedBy: "user",
        cascade: ["persist", "remove", "merge"],
        orphanRemoval: true)
    ]
    public $phonenumbers;
}

<?php

namespace Repositories\Indexers;

use Repositories\RepositoryWriterInterface;
use CustomUtility\ValueUtil;

class Item implements IndexerInterface
{
    protected $types;

    const DEFAULT_INDEX = "item";

    public function __construct()
    {
        // this is a hash with the valid item types
        $this->types = array_flip(array(
            "AMMO", "GUN", "ARMOR", "TOOL", "TOOL_ARMOR", "BOOK", "COMESTIBLE",
            "CONTAINER", "GUNMOD", "GENERIC", "BIONIC_ITEM", "VAR_VEH_PART",
            "_SPECIAL", "MAGAZINE", "WHEEL", "TOOLMOD", "ENGINE", "VEHICLE_PART",
            "PET_ARMOR",
        ));

        $this->book_types = array(
            "archery" => "range",
            "handguns" => "range",
            "markmanship" => "range",
            "launcher" => "range",
            "firearms" => "range",
            "throw" => "range",
            "rifle" => "range",
            "shotgun" => "range",
            "smg" => "range",
            "pistol" => "range",
            "gun" => "range",
            "bashing" => "combat",
            "cutting" => "combat",
            "stabbing" => "combat",
            "dodge" => "combat",
            "melee" => "combat",
            "unarmed" => "combat",
            "computer" => "engineering",
            "electronics" => "engineering",
            "fabrication" => "engineering",
            "mechanics" => "engineering",
            "construction" => "engineering",
            "carpentry" => "engineering",
            "traps" => "engineering",
            "tailor" => "crafts",
            "firstaid" => "crafts",
            "cooking" => "crafts",
            "barter" => "social",
            "speech" => "social",
            "driving" => "survival",
            "survival" => "survival",
            "swimming" => "survival",
            "none" => "fun",
        );
    }

    public function onFinishedLoading(RepositoryWriterInterface $repo)
    {
        $starttime = microtime(true);
        foreach ($repo->raw(self::DEFAULT_INDEX) as $id) {
            $recipes = count($repo->raw("item.toolFor.$id"));
            if ($recipes > 0) {
                $repo->set("item.count.$id.toolFor", $recipes);
            }

            $recipes = count($repo->raw("item.recipes.$id"));
            if ($recipes > 0) {
                $repo->set("item.count.$id.recipes", $recipes);
            }

            $recipes = count($repo->raw("item.learn.$id"));
            if ($recipes > 0) {
                $repo->set("item.count.$id.learn", $recipes);
            }

            $recipes = count($repo->raw("item.disassembly.$id"));
            if ($recipes > 0) {
                $repo->set("item.count.$id.disassembly", $recipes);
            }

            $recipes = count($repo->raw("item.disassembledFrom.$id"));
            if ($recipes > 0) {
                $repo->set("item.count.$id.disassembledFrom", $recipes);
            }

            $recipes = count($repo->raw("item.uncraftToolFor.$id"));
            if ($recipes > 0) {
                $repo->set("item.count.$id.uncraftToolFor", $recipes);
            }

            $count = count($repo->raw("construction.$id"));
            if ($count > 0) {
                $repo->set("item.count.$id.construction", $count);
            }

            // sort item recipes, by difficulty
            $categories = $repo->raw("item.categories.$id");
            foreach ($categories as $category) {
                $recipes = $repo->raw("item.toolForCategory.$id.$category");
                usort($recipes, function ($a, $b) use ($repo) {
                    $a = $repo->get("recipe.$a");
                    $b = $repo->get("recipe.$b");

                    return $a->difficulty - $b->difficulty;
                });
                $repo->set("item.toolForCategory.$id.$category", $recipes);
            }

            // build item/vehicle part installation cross reference list per item
            if (strpos($id, "vpart_") === 0) {
                $vpart = $repo->get(self::DEFAULT_INDEX.".".$id);
                if (isset($vpart->item)) {
                    $repo->append("vpartlist.$vpart->item", $id);
                }
            }
        }

        $repo->sort("flags");
        $repo->sort("gunmodParts");
        $repo->sort("gunmodSkills");
        $repo->sort("armorParts");
        $repo->sort("gunSkills");
        $repo->sort("bookSkills");
        $repo->sort("consumableTypes");

        $timediff = microtime(true) - $starttime;
        echo "Item post-processing ".number_format($timediff, 3)." s.\n";
    }

    public function onNewObject(RepositoryWriterInterface $repo, $object)
    {
        // capitalize type name to avoid failing on lowercase types
        if (!isset($this->types[strtoupper($object->type)]) || !isset($object->id)) {
            return;
        }

        $repo->append(self::DEFAULT_INDEX, $object->id);
        $repo->set(self::DEFAULT_INDEX.".".$object->id, $object->repo_id);

        // nearby fire and integrated toolset are "virtual" items
        // they don't have anything special.
        // also exclude abstract objects
        if ($object->type == "_SPECIAL" || array_key_exists("abstract", $object)) {
            return;
        }

        ValueUtil::SetDefault($object, "reload", 100);
        ValueUtil::SetDefault($object, "to_hit", 0);
        if ($object->type == "ARMOR" || $object->type == "TOOL_ARMOR") {
            ValueUtil::SetDefault($object, "environmental_protection", 0);
            ValueUtil::SetDefault($object, "encumbrance", 0);
        }
        if ($object->type == "BOOK") {
            ValueUtil::SetDefault($object, "skill", "none");
            ValueUtil::SetDefault($object, "required_level", 0);
        }
        if ($object->type == "GUN") {
            ValueUtil::SetDefault($object, "skill", "none");
            ValueUtil::SetDefault($object, "ranged_damage", 0);
            ValueUtil::SetDefault($object, "range", 0);
            ValueUtil::SetDefault($object, "recoil", 0);
            ValueUtil::SetDefault($object, "dispersion", 120);
            ValueUtil::SetDefault($object, "burst", 0);
        }
        if ($object->type == "GUNMOD") {
            ValueUtil::SetDefault($object, "location", "unknown");
            ValueUtil::SetDefault($object, "mod_targets", array("unknown_target"));
        }
        if ($object->type == "AMMO") {
            ValueUtil::SetDefault($object, "damage", 0);
            ValueUtil::SetDefault($object, "recoil", 0);
            ValueUtil::SetDefault($object, "loudness", 0);
            ValueUtil::SetDefault($object, "price", 0);
            ValueUtil::SetDefault($object, "pierce", 0);
            ValueUtil::SetDefault($object, "dispersion", 0);
            ValueUtil::SetDefault($object, "count", 1);
        }
        if ($object->type == "COMESTIBLE") {
            ValueUtil::SetDefault($object, "comestible_type", "None");
            ValueUtil::SetDefault($object, "phase", "solid");
            ValueUtil::SetDefault($object, "quench", 0);
            ValueUtil::SetDefault($object, "fun", 0);
            ValueUtil::SetDefault($object, "healthy", 0);
            ValueUtil::SetDefault($object, "addiction_potential", 0);
            ValueUtil::SetDefault($object, "charges", 1);
        }

        // handle properties that are modified by addition/multiplication
        // the property is removed after application, since each template reference can have its own modifiers
        if (isset($object->relative)) {
            foreach ($object->relative as $relkey => $relvalue) {
                if (isset($object->{$relkey})) {
                    $object->{$relkey} += $relvalue;
                }
            }
            unset($object->relative);
        }

        if (isset($object->proportional)) {
            foreach ($object->proportional as $proportionkey => $proportionvalue) {
                if (!isset($object->{$proportionkey}) || is_array($object->{$proportionkey})) {
                    continue;
                }
                $object->{$proportionkey} = floor($object->{$proportionkey} * $proportionvalue);
            }
            unset($object->proportional);
        }

        // items with enough damage might be good melee weapons.
        $damagecheck = 0;
        if (isset($object->bashing)) {
            $damagecheck += $object->bashing;
        }
        if (isset($object->cutting)) {
            $damagecheck += $object->cutting;
        }
        if (isset($object->to_hit)) {
            $damagecheck += $object->to_hit;
        }
        if ($damagecheck >= 8 && strtoupper($object->type) != "VEHICLE_PART" && isset($object->weight) && $object->weight < 15000 && (!isset($object->dispersion) || $object->dispersion == 0)) {
            $repo->append("melee", $object->id);
        }

        $is_armor = in_array($object->type, ["ARMOR", "TOOL_ARMOR"]);

        // create an index with armor for each body part they cover.
        if ($is_armor and !isset($object->covers)) {
            $repo->append("armor.none", $object->id);
        } elseif ($is_armor and isset($object->covers)) {
            foreach ($object->covers as $part) {
                $part = strtolower($part);
                $repo->append("armor.$part", $object->id);
                $repo->addUnique("armorParts", $part);
            }
        }

        // handle "ml" indicators in volume/container volume
        if (isset($object->volume) && is_string($object->volume)) {
            if (stripos($object->volume, "ml") !== false) {
                $object->volume = (substr($object->volume, 0, -2) * 4.0) / 1000.0;
            }
        }
        if (isset($object->contains) && is_string($object->contains)) {
            if (stripos($object->contains, "ml") !== false) {
                $object->contains = substr($object->contains, 0, -2) / 1000.0;
            }
        }
        // adjust volume for low volume large stack size ammunition
        if ($object->type == "AMMO") {
            if (isset($object->stack_size) && $object->stack_size > 0) {
                if (($object->volume * 1.0) / $object->stack_size < .004) {
                    $object->volume = .004 * $object->stack_size;
                }
            }
        }

        if ($object->type == "CONTAINER") {
            $repo->append("container", $object->id);
        }
        if ($object->type == "COMESTIBLE") {
            $repo->append("food", $object->id);
        }
        if ($object->type == "TOOL") {
            $repo->append("tool", $object->id);
        }

        //apply substitution saving
        if (isset($object->sub)) {
            $repo->add_substitute($object->id, $object->sub);
        }

        // save books per skill
        if ($object->type == "BOOK") {
            if (isset($this->book_types[$object->skill])) {
                $skill = $this->book_types[$object->skill];
            } else {
                $skill = "other";
            }
            $repo->append("book.$skill", $object->id);
            $repo->addUnique("bookSkills", $skill);
        }

        if ($object->type == "GUN") {
            if (is_object($object->ranged_damage)) {
                $object->ranged_damage = "N/A";
            }
            if (!isset($object->skill)) {
                $object->skill = "none";
            }
            $repo->append("gun.$object->skill", $object->id);
            $repo->addUnique("gunSkills", $object->skill);
        }

        if ($object->type == "GUNMOD") {
            foreach ($object->mod_targets as $target) {
                $repo->append("gunmods.$target.$object->location", $object->id);
                $repo->addUnique("gunmodSkills", $target);
            }
            $repo->addUnique("gunmodParts", $object->location);
        }

        if ($object->type == "AMMO") {
            if (isset($object->ammo_type)) {
                // some ammunition has multiple types
                if (is_array($object->ammo_type)) {
                    foreach ($object->ammo_type as $minitype) {
                        $repo->append("ammo.$minitype", $object->id);
                    }
                } else {
                    $repo->append("ammo.$object->ammo_type", $object->id);
                }
            }
        }

        if ($object->type == "COMESTIBLE") {
            if (!isset($object->calories) && isset($object->nutrition)) {
                $object->calories = floor($object->nutrition * 2500.0 / 288.0);
            } elseif (isset($object->calories)) {
                $object->calories = floor(floor($object->calories / 2500.0 * 288.0) * 2500.0 / 288.0);
            } else {
                ValueUtil::SetDefault($object, "nutrition", 0);
                ValueUtil::SetDefault($object, "calories", 0);
            }
            $object->nutrition = $object->calories;

            $type = strtolower($object->comestible_type);

            if (isset($object->brewable)) {
                $repo->append("consumables.fermentable", $object->id);
                $repo->addUnique("consumableTypes", "fermentable");
            } else {
                $repo->append("consumables.$type", $object->id);
                $repo->addUnique("consumableTypes", $type);
            }
        }

        if (isset($object->qualities)) {
            foreach ($object->qualities as $quality) {
                $repo->append("quality.$quality[0]", $object->id);
            }
        }

        if (isset($object->material)) {
            $materials = (array) $object->material;
            $repo->append("material.$materials[0]", $object->id);
            if (count($object->material) > 1 and $materials[1] != "null") {
                $repo->append("material.$materials[1]", $object->id);
            }
        }

        if (isset($object->flags)) {
            $flags = (array) $object->flags;
            foreach ($flags as $flag) {
                if ($flag != "") {
                    $repo->append("flag.$flag", $object->id);
                    $repo->addUnique("flags", $flag);
                }
            }
        }
    }
}

<?php

namespace flowcode\ceibo\builder;

use flowcode\ceibo\builder\MapperBuilder;
use flowcode\ceibo\domain\Mapper;
use flowcode\ceibo\domain\Relation;

/**
 * Description of QueryBuilder
 *
 * @author JMA <jaguero@flowcode.com.ar>
 */
class QueryBuilder {

    /**
     * Build a delete query for an entity.
     * @param type $entity
     * @return string 
     */
    public static function buildDeleteQuery(Mapper $mapper) {
        $query = "DELETE FROM " . $mapper->getTable() . " ";
        $query .= "WHERE id = :id;";
        return $query;
    }

    /**
     * Build a delete query for a relation.
     * @param type $relation
     * @return string 
     */
    public static function buildDeleteRelationQuery(Relation $relation) {
        $query = "DELETE FROM `" . $relation->getTable() . "` ";
        $query .= "WHERE " . $relation->getLocalColumn() . " = :id;";
        return $query;
    }

    /**
     * Return the entity insert query.
     * @param type $entity
     * @return string 
     */
    public static function buildInsertQuery($entity, Mapper $mapper) {
        $fields = "";
        $values = "";
        foreach ($mapper->getPropertys() as $property) {
            if ($property->getColumn() != "id") {
                $fields .= "`" . $property->getColumn() . "`, ";

                if ($property->isNumeric()) {
                    $values .= ":" . $property->getColumn() . ", ";
                } else {
                    $values .= ":" . $property->getColumn() . ", ";
                }
            }
        }

        $fields = substr_replace($fields, "", -2);
        $values = substr_replace($values, "", -2);

        $query = "INSERT INTO `" . $mapper->getTable() . "` (" . $fields . ") VALUES (" . $values . ")";

        return $query;
    }

    /**
     * Return the insert relation query.
     * @param type $entity
     * @param Relation $relation
     * @return string $query.
     */
    public static function buildRelationQuery($entity, Relation $relation) {
        $relQuery = "";
        $getid = "getId";
        if ($relation->getCardinality() == Relation::$manyToMany) {
            $m = "get" . $relation->getName();
            $relQuery .= "INSERT INTO " . $relation->getTable() . " (" . $relation->getLocalColumn() . ", " . $relation->getForeignColumn() . ") ";
            $relQuery .= "VALUES (:" . $relation->getLocalColumn() . ", :" . $relation->getForeignColumn() . ");";
        }
        if ($relation->getCardinality() == Relation::$oneToMany) {
            $relMapper = MapperBuilder::buildFromName($this->mapping, $relation->getEntity());
            $m = "get" . $relation->getName();
            foreach ($entity->$m() as $rel) {
                $setid = "set" . $relMapper->getNameForColumn($relation->getForeignColumn());
                $rel->$setid($entity->$getid());
                $relQuery .= $this->buildInsertQuery($rel);
            }
        }


        return $relQuery;
    }

    /**
     * Return the update query for the entity.
     * @param type $entity
     * @param Mapper $mapper
     * @return string
     */
    public static function buildUpdateQuery($entity, Mapper $mapper) {
        $fields = "";
        foreach ($mapper->getPropertys() as $property) {
            if ($property->getColumn() != "id") {
                $method = "get" . $property->getName();
                $entity->$method();

                if ($property->isNumeric()) {
                    $fieldValue = "`=:" . $property->getColumn() . ", ";
                } else {
                    $fieldValue = "`=:" . $property->getColumn() . ", ";
                }
                $fields .= "`" . $property->getColumn() . $fieldValue;
            }
        }
        $fields = substr_replace($fields, "", -2);
        $query = "UPDATE `" . $mapper->getTable() . "` SET " . $fields . " WHERE id=:id";

        return $query;
    }

    /**
     * Get the query for select the related entitys.
     * @param type $entity
     * @param type $relation Name of the relation.
     */
    public static function buildSelectRelation($relation, $mapperRelation) {
        $query = "";

        $fields = "";
        foreach ($mapperRelation->getPropertys() as $property) {
            $fields .= "c." . $property->getColumn() . ", ";
        }
        $fields = substr_replace($fields, "", -2);

        if ($relation->getCardinality() == Relation::$manyToMany) {
            $query = "select " . $fields . " from " . $mapperRelation->getTable() . " c ";
            $query .= "inner join " . $relation->getTable() . " nc on nc." . $relation->getForeignColumn() . " = c.id ";
            $query .= "where nc." . $relation->getLocalColumn() . " = :id";
        }
        if ($relation->getCardinality() == Relation::$oneToMany) {
            $query = "select " . $fields . " from " . $mapperRelation->getTable() . " c ";
            $query .= "where c." . $relation->getForeignColumn() . " = :id";
        }
        return $query;
    }

    public static function buildJoinRelationQuery(Relation $relation, $mainSynonym, $joinSynonym) {
        $query = "";
        if ($relation->getCardinality() == Relation::$manyToMany) {
            $query .= "INNER JOIN " . $relation->getTable() . " $joinSynonym ";
            $query .= "ON $joinSynonym." . $relation->getForeignColumn() . " = " . $mainSynonym . ".id ";
        }

        return $query;
    }

    public static function getInsertRelation($entity, $relation) {
        $relQuery = "";
        $getid = "getId";
        if ($relation->getCardinality() == Relation::$manyToMany) {
            $m = "get" . $relation->getName();
            foreach ($entity->$m() as $rel) {
                $relQuery .= "INSERT INTO " . $relation->getTable() . " (" . $relation->getLocalColumn() . ", " . $relation->getForeignColumn() . ") ";
                $relQuery .= "VALUES ('" . $entity->$getid() . "', '" . $rel->$getid() . "');";
            }
        }
        if ($relation->getCardinality() == Relation::$oneToMany) {
            $relMapper = MapperBuilder::buildFromName($this->mapping, $relation->getEntity());
            $m = "get" . $relation->getName();
            foreach ($entity->$m() as $rel) {
                $setid = "set" . $relMapper->getNameForColumn($relation->getForeignColumn());
                $rel->$setid($entity->$getid());
                $relQuery .= $this->buildInsertQuery($rel);
            }
        }

        return $relQuery;
    }

    public function getDeleteQuery(Mapper $mapper) {
        $query = self::buildDeleteQuery($mapper);
        return $query;
    }

    public function getDeleteRelationQuery($relation) {
        $query = self::buildDeleteRelationQuery($relation);
        return $query;
    }

    public function getInsertQuery($entity, Mapper $mapper) {
        $query = self::buildInsertQuery($entity, $mapper);
        return $query;
    }

    public function getRelationQuery($entity, Relation $relation) {
        $query = self::buildRelationQuery($entity, $relation);
        return $query;
    }

    public function getUpdateQuery($entity, Mapper $mapper) {
        $query = self::buildUpdateQuery($entity, $mapper);
        return $query;
    }

    public function getSelectRelationQuery($entity, $relation, $mapperRelation) {
        $query = self::buildSelectRelation($entity, $relation, $mapperRelation);
        return $query;
    }

    public function getJoinRelationQuery(Relation $relation, $mainSynonym, $joinSynonym) {
        $query = self::buildJoinRelationQuery($relation, $mainSynonym, $joinSynonym);
        return $query;
    }

}

?>

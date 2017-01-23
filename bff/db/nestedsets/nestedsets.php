<?php 
namespace bff\db;
class NestedSetsTree extends \Component
{
    protected $table = "";
    protected $id = "id";
    protected $pid = "pid";
    protected $groupField = "group_id";
    protected $groupID = 0;
    protected $useGroups = false;

    public function __construct($b9b74b3f454c11054, $x606b28a = false, $Dae5ef29 = false, $d705c6ddb38 = false, $f5a4e54 = "group_id")
    {
        $this->table = $b9b74b3f454c11054;
        if( !empty($x606b28a) ) 
        {
            $this->id = $x606b28a;
        }

        if( !empty($Dae5ef29) ) 
        {
            $this->pid = $Dae5ef29;
        }

        $this->useGroups = $d705c6ddb38;
        if( $d705c6ddb38 ) 
        {
            $this->groupField = $f5a4e54;
        }

    }

    public function setGroupID($fd971304c28 = 0)
    {
        $this->groupID = (int) $fd971304c28;
    }

    public function getNodePath($z1914afbd178ef0, &$da4bb298d, $e733ec89 = " AND numlevel>1 ")
    {
        $A0c027a94ce622 = (int) $this->db->one_data("SELECT " . $this->pid . " FROM " . $this->table . " WHERE " . $this->id . " = :id " . $e733ec89, array( ":id" => $z1914afbd178ef0 ));
        if( !$A0c027a94ce622 ) 
        {
            return NULL;
        }

        $da4bb298d[] = $A0c027a94ce622;
        $this->getNodePath($A0c027a94ce622, $da4bb298d);
    }

    public function getNodeParentsID($S872a4307f677fdbde9, $H02f865b = " AND numlevel>0 ", $Z21800d6 = false, $Xcfb29e18 = array(  ))
    {
        $A0c027a94ce623 = $this->getNodeInfo($S872a4307f677fdbde9);
        $A0c027a94ce624 = "select_one_column";
        if( empty($Xcfb29e18) ) 
        {
            $Xcfb29e18 = array( $this->id );
        }
        else
        {
            if( 1 < sizeof($Xcfb29e18) || sizeof($Xcfb29e18) === 1 && current($Xcfb29e18) == "*" ) 
            {
                $A0c027a94ce624 = "select";
            }

        }

        return $this->db->$A0c027a94ce624("SELECT " . join(",", $Xcfb29e18) . " FROM " . $this->table . " WHERE ((numleft <= " . $A0c027a94ce623["numleft"] . " AND numright > " . $A0c027a94ce623["numright"] . ")" . ($Z21800d6 ? " OR " . $this->id . " = " . $S872a4307f677fdbde9 : "") . ") " . $H02f865b . " ORDER BY numleft");
    }

    public function getNodeParentsAndChildrensID($afc45f85a, $b1a59b315fc9a300 = " AND numlevel>1 ")
    {
    }

    public function getNodeInfo($F0675c254)
    {
        return $this->db->one_array("SELECT * FROM " . $this->table . " WHERE " . $this->id . " = :id LIMIT 1", array( ":id" => $F0675c254 ));
    }

    public function getNodeNumlevel($cdaf4af7b1)
    {
        return (int) $this->db->one_data("SELECT numlevel FROM " . $this->table . " WHERE " . $this->id . " = :id", array( ":id" => $cdaf4af7b1 ));
    }

    public function getChildrenCount($c742bc5b217fd6, $bdc84c5734f78 = "")
    {
        return (int) $this->db->one_data("SELECT (numright-numleft)-1 FROM " . $this->table . " WHERE " . $this->id . " = :id" . " " . $bdc84c5734f78 . " LIMIT 1", array( ":id" => $c742bc5b217fd6 ));
    }

    public function checkRootNode(&$z1032f69c481, $f8818b67ab25 = 0)
    {
        $z1032f69c481 = $this->getRootNodeID($f8818b67ab25, $A0c027a94ce625);
        return $A0c027a94ce625;
    }

    public function getRootNodeID($ef78abe13a5466f3 = 0, &$Xc22d252d22)
    {
        if( $this->useGroups ) 
        {
            if( empty($ef78abe13a5466f3) ) 
            {
                $ef78abe13a5466f3 = $this->groupID;
            }
            else
            {
                $ef78abe13a5466f3 = (int) $ef78abe13a5466f3;
            }

        }

        $A0c027a94ce626 = (int) $this->db->one_data("SELECT " . $this->id . " FROM " . $this->table . " WHERE " . ($this->useGroups ? $this->groupField . "=\"" . $ef78abe13a5466f3 . "\" AND" : "") . " " . $this->pid . " = 0");
        if( !$A0c027a94ce626 ) 
        {
            $A0c027a94ce627 = array( $this->pid => 0, "numlevel" => 0, "numleft" => 1, "numright" => 2 );
            if( $this->useGroups ) 
            {
                $A0c027a94ce627[$this->groupField] = $ef78abe13a5466f3;
            }

            $A0c027a94ce626 = $this->db->insert($this->table, $A0c027a94ce627, $this->id);
            $Xc22d252d22 = true;
        }
        else
        {
            $Xc22d252d22 = false;
        }

        return $A0c027a94ce626;
    }

    public function insertNode($b2786b72105845f = 0, $B90dff3 = 0, $Db36db0 = 0)
    {
        $b2786b72105845f = (int) $b2786b72105845f;
        if( !$b2786b72105845f ) 
        {
            $b2786b72105845f = $this->getRootNodeID($Db36db0, $A0c027a94ce628);
        }

        return $this->insertNodeByParentId($b2786b72105845f, $B90dff3);
    }

    public function insertNodeByParentId($o0c8af8db7f2538, $c1dfddcce0d821d8 = false)
    {
        $A0c027a94ce629 = $this->getNodeInfo($o0c8af8db7f2538);
        if( !$A0c027a94ce629 ) 
        {
            return false;
        }

        $A0c027a94ce630 = "UPDATE " . $this->table . " SET numright=numright+2, numleft = (CASE WHEN (numleft>" . $A0c027a94ce629["numright"] . ") THEN (numleft+2) ELSE (numleft) END) WHERE numright>=" . $A0c027a94ce629["numright"] . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce629[$this->groupField]) : "");
        $this->db->exec($A0c027a94ce630);
        $A0c027a94ce631 = array( $this->pid => $o0c8af8db7f2538, "numleft" => $A0c027a94ce629["numright"], "numright" => $A0c027a94ce629["numright"] + 1, "numlevel" => $A0c027a94ce629["numlevel"] + 1 );
        if( $this->useGroups ) 
        {
            $A0c027a94ce631[$this->groupField] = $A0c027a94ce629[$this->groupField];
        }

        if( !empty($c1dfddcce0d821d8) && 0 < $c1dfddcce0d821d8 ) 
        {
            $A0c027a94ce631[$this->id] = $c1dfddcce0d821d8;
            return $this->db->insert($this->table, $A0c027a94ce631, false);
        }

        return $this->db->insert($this->table, $A0c027a94ce631, $this->id);
    }

    public function deleteNode($Heb719bb0)
    {
        $A0c027a94ce632 = $this->getNodeInfo($Heb719bb0);
        if( !$A0c027a94ce632 ) 
        {
            return false;
        }

        $A0c027a94ce633 = $this->db->select_one_column("SELECT " . $this->id . " FROM " . $this->table . " WHERE numleft>=" . $A0c027a94ce632["numleft"] . " AND numright<=" . $A0c027a94ce632["numright"] . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce632[$this->groupField]) : ""));
        $this->db->exec("DELETE FROM " . $this->table . " WHERE numleft>=" . $A0c027a94ce632["numleft"] . " AND numright<=" . $A0c027a94ce632["numright"] . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce632[$this->groupField]) : ""));
        $this->db->exec("UPDATE " . $this->table . " SET numright=(numright-" . $A0c027a94ce632["numright"] . "+" . $A0c027a94ce632["numleft"] . "-1) WHERE numright > " . $A0c027a94ce632["numright"] . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce632[$this->groupField]) : ""));
        $this->db->exec("UPDATE " . $this->table . " SET numleft=(numleft-" . $A0c027a94ce632["numright"] . "+" . $A0c027a94ce632["numleft"] . "-1) WHERE numleft > " . $A0c027a94ce632["numleft"] . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce632[$this->groupField]) : ""));
        return $A0c027a94ce633;
    }

    public function moveNodeUp($r4011421ae8a7)
    {
        $A0c027a94ce634 = $this->getNodeInfo($r4011421ae8a7);
        if( !$A0c027a94ce634 ) 
        {
            return false;
        }

        $A0c027a94ce635 = "SELECT * FROM " . $this->table . " WHERE numleft<" . $A0c027a94ce634["numleft"] . " AND numlevel=" . $A0c027a94ce634["numlevel"] . " AND " . $this->pid . " = :pid " . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce634[$this->groupField]) : "") . " ORDER BY numleft DESC LIMIT 1";
        $A0c027a94ce636 = $this->db->one_array($A0c027a94ce635, array( ":pid" => $A0c027a94ce634[$this->pid] ));
        if( !$A0c027a94ce636 ) 
        {
            return false;
        }

        if( $A0c027a94ce636["numright"] - $A0c027a94ce636["numleft"] == 1 && $A0c027a94ce634["numright"] - $A0c027a94ce634["numleft"] == 1 ) 
        {
            $this->changePosition($A0c027a94ce634, $A0c027a94ce636);
        }
        else
        {
            $this->changePositionAll($A0c027a94ce634, $A0c027a94ce636, "before");
        }

        return true;
    }

    public function moveNodeDown($a5c25b7cac01)
    {
        $A0c027a94ce637 = $this->getNodeInfo($a5c25b7cac01);
        if( !$A0c027a94ce637 ) 
        {
            return false;
        }

        $A0c027a94ce638 = "SELECT * FROM " . $this->table . " WHERE numleft>" . $A0c027a94ce637["numleft"] . " AND numlevel=" . $A0c027a94ce637["numlevel"] . " AND " . $this->pid . " = :pid " . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce637[$this->groupField]) : "") . " ORDER BY numleft ASC LIMIT 1";
        $A0c027a94ce639 = $this->db->one_array($A0c027a94ce638, array( ":pid" => $A0c027a94ce637[$this->pid] ));
        if( !$A0c027a94ce639 ) 
        {
            return false;
        }

        if( $A0c027a94ce639["numright"] - $A0c027a94ce639["numleft"] == 1 && $A0c027a94ce637["numright"] - $A0c027a94ce637["numleft"] == 1 ) 
        {
            $this->changePosition($A0c027a94ce637, $A0c027a94ce639);
        }
        else
        {
            $this->changePositionAll($A0c027a94ce637, $A0c027a94ce639, "after");
        }

        return true;
    }

    public function changeParent($d7a2e96d6, $zb6dde8d346)
    {
        if( $d7a2e96d6 <= 0 || $zb6dde8d346 <= 0 || $d7a2e96d6 == $zb6dde8d346 ) 
        {
            return false;
        }

        $A0c027a94ce640 = $this->getNodeInfo($d7a2e96d6);
        if( !$A0c027a94ce640 ) 
        {
            return false;
        }

        $A0c027a94ce641 = $A0c027a94ce640["numright"] - $A0c027a94ce640["numleft"] + 1;
        $A0c027a94ce642 = $this->getNodeInfo($zb6dde8d346);
        if( !$A0c027a94ce642 ) 
        {
            return false;
        }

        if( $A0c027a94ce640["numleft"] < $A0c027a94ce642["numleft"] && $A0c027a94ce642["numright"] < $A0c027a94ce640["numright"] ) 
        {
            return false;
        }

        $this->db->update($this->table, array( "numleft = 0 - numleft", "numright = 0 - numright" ), array( "numleft >= :left", "numright <= :right" ), array( ":left" => $A0c027a94ce640["numleft"], ":right" => $A0c027a94ce640["numright"] ));
        $this->db->update($this->table, array( "numleft = numleft - :size" ), array( "numleft > :right" ), array( ":size" => $A0c027a94ce641, ":right" => $A0c027a94ce640["numright"] ));
        $this->db->update($this->table, array( "numright = numright - :size" ), array( "numright > :right" ), array( ":size" => $A0c027a94ce641, ":right" => $A0c027a94ce640["numright"] ));
        $this->db->update($this->table, array( "numleft = numleft + :size" ), array( "numleft >= :x" ), array( ":size" => $A0c027a94ce641, ":x" => $A0c027a94ce640["numright"] < $A0c027a94ce642["numright"] ? $A0c027a94ce642["numright"] - $A0c027a94ce641 : $A0c027a94ce642["numright"] ));
        $this->db->update($this->table, array( "numright = numright + :size" ), array( "numright >= :x" ), array( ":size" => $A0c027a94ce641, ":x" => $A0c027a94ce640["numright"] < $A0c027a94ce642["numright"] ? $A0c027a94ce642["numright"] - $A0c027a94ce641 : $A0c027a94ce642["numright"] ));
        $A0c027a94ce643 = "";
        if( $A0c027a94ce640["numlevel"] <= $A0c027a94ce642["numlevel"] ) 
        {
            $A0c027a94ce643 = "+" . ($A0c027a94ce642["numlevel"] - $A0c027a94ce640["numlevel"] + 1);
        }
        else
        {
            if( $A0c027a94ce642["numlevel"] < $A0c027a94ce640["numlevel"] && $A0c027a94ce640["numlevel"] - 1 != $A0c027a94ce642["numlevel"] ) 
            {
                $A0c027a94ce643 = "-" . (abs($A0c027a94ce640["numlevel"] - $A0c027a94ce642["numlevel"]) - 1);
            }

        }

        $this->db->update($this->table, array( "numleft = 0 - numleft + :x", "numright = 0 - numright + :x", "numlevel = numlevel " . $A0c027a94ce643 ), array( "numleft <= :left", "numright >= :right" ), array( ":x" => $A0c027a94ce640["numright"] < $A0c027a94ce642["numright"] ? $A0c027a94ce642["numright"] - $A0c027a94ce640["numright"] - 1 : $A0c027a94ce642["numright"] - $A0c027a94ce640["numright"] - 1 + $A0c027a94ce641, ":left" => 0 - $A0c027a94ce640["numleft"], ":right" => 0 - $A0c027a94ce640["numright"] ));
        $this->db->update($this->table, array( $this->pid => $zb6dde8d346 ), array( $this->id => $d7a2e96d6 ));
        return true;
    }

    public function changePosition($r742ebb4522933, $e36315c)
    {
        $this->db->update($this->table, array( "numleft" => $r742ebb4522933["numleft"], "numright" => $r742ebb4522933["numright"], "numlevel" => $r742ebb4522933["numlevel"], $this->pid => $r742ebb4522933[$this->pid] ), $this->id . " = :id", array( ":id" => $e36315c[$this->id] ));
        $this->db->update($this->table, array( "numleft" => $e36315c["numleft"], "numright" => $e36315c["numright"], "numlevel" => $e36315c["numlevel"], $this->pid => $e36315c[$this->pid] ), $this->id . " = :id", array( ":id" => $r742ebb4522933[$this->id] ));
    }

    public function rotateTablednd($Vb4d07009bf1d93f = "dnd-")
    {
        $A0c027a94ce644 = intval(str_replace($Vb4d07009bf1d93f, "", !empty($_POST["dragged"]) ? $_POST["dragged"] : ""));
        if( $A0c027a94ce644 <= 0 ) 
        {
            break;
        }

        $A0c027a94ce645 = intval(str_replace($Vb4d07009bf1d93f, "", !empty($_POST["target"]) ? $_POST["target"] : ""));
        if( $A0c027a94ce645 <= 0 ) 
        {
            break;
        }

        $A0c027a94ce646 = isset($_POST["position"]) ? trim($_POST["position"]) : "";
        if( empty($A0c027a94ce646) || !in_array($A0c027a94ce646, array( "after", "before" )) ) 
        {
            break;
        }

        $A0c027a94ce647 = $this->getNodeInfo($A0c027a94ce644);
        $A0c027a94ce648 = $this->getNodeInfo($A0c027a94ce645);
        return $this->changePositionAll($A0c027a94ce647, $A0c027a94ce648, $A0c027a94ce646);
    }

    public function changePositionAll($hfad5df9eeac2954b, $d134e53923085eae8d, $Oa29f1cd56a00 = "after")
    {
        $A0c027a94ce649 = $hfad5df9eeac2954b["numleft"];
        $A0c027a94ce650 = $d134e53923085eae8d["numleft"];
        $A0c027a94ce651 = $hfad5df9eeac2954b["numright"];
        $A0c027a94ce652 = $d134e53923085eae8d["numright"];
        if( $Oa29f1cd56a00 == "before" ) 
        {
            if( $A0c027a94ce650 < $A0c027a94ce649 ) 
            {
                $A0c027a94ce653 = "UPDATE " . $this->table . " SET numright = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numright - " . ($A0c027a94ce649 - $A0c027a94ce650) . " WHEN numleft BETWEEN " . $A0c027a94ce650 . " AND " . ($A0c027a94ce649 - 1) . " THEN numright +  " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numright END, numleft = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numleft - " . ($A0c027a94ce649 - $A0c027a94ce650) . " WHEN numleft BETWEEN " . $A0c027a94ce650 . " AND " . ($A0c027a94ce649 - 1) . " THEN numleft + " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numleft END WHERE numleft BETWEEN " . $A0c027a94ce650 . " AND " . $A0c027a94ce651;
            }
            else
            {
                $A0c027a94ce653 = "UPDATE " . $this->table . " SET numright = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numright + " . ($A0c027a94ce650 - $A0c027a94ce649 - ($A0c027a94ce651 - $A0c027a94ce649 + 1)) . " WHEN numleft BETWEEN " . ($A0c027a94ce651 + 1) . " AND " . ($A0c027a94ce650 - 1) . " THEN numright - " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numright END, numleft = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numleft + " . ($A0c027a94ce650 - $A0c027a94ce649 - ($A0c027a94ce651 - $A0c027a94ce649 + 1)) . " WHEN numleft BETWEEN " . ($A0c027a94ce651 + 1) . " AND " . ($A0c027a94ce650 - 1) . " THEN numleft - " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numleft END WHERE numleft BETWEEN " . $A0c027a94ce649 . " AND " . ($A0c027a94ce650 - 1);
            }

        }

        if( $Oa29f1cd56a00 == "after" ) 
        {
            if( $A0c027a94ce650 < $A0c027a94ce649 ) 
            {
                $A0c027a94ce653 = "UPDATE " . $this->table . " SET numright = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numright - " . ($A0c027a94ce649 - $A0c027a94ce650 - ($A0c027a94ce652 - $A0c027a94ce650 + 1)) . " WHEN numleft BETWEEN " . ($A0c027a94ce652 + 1) . " AND " . ($A0c027a94ce649 - 1) . " THEN numright +  " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numright END, numleft = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numleft - " . ($A0c027a94ce649 - $A0c027a94ce650 - ($A0c027a94ce652 - $A0c027a94ce650 + 1)) . " WHEN numleft BETWEEN " . ($A0c027a94ce652 + 1) . " AND " . ($A0c027a94ce649 - 1) . " THEN numleft + " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numleft END WHERE numleft BETWEEN " . ($A0c027a94ce652 + 1) . " AND " . $A0c027a94ce651;
            }
            else
            {
                $A0c027a94ce653 = "UPDATE " . $this->table . " SET numright = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numright + " . ($A0c027a94ce652 - $A0c027a94ce651) . " WHEN numleft BETWEEN " . ($A0c027a94ce651 + 1) . " AND " . $A0c027a94ce652 . " THEN numright - " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numright END, numleft = CASE WHEN numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce651 . " THEN numleft + " . ($A0c027a94ce652 - $A0c027a94ce651) . " WHEN numleft BETWEEN " . ($A0c027a94ce651 + 1) . " AND " . $A0c027a94ce652 . " THEN numleft - " . ($A0c027a94ce651 - $A0c027a94ce649 + 1) . " ELSE numleft END WHERE numleft BETWEEN " . $A0c027a94ce649 . " AND " . $A0c027a94ce652;
            }

        }

        if( isset($A0c027a94ce653) ) 
        {
            return $this->db->exec($A0c027a94ce653);
        }

        return false;
    }

    public function toggleNodeEnabled($Yc5381cbe4f46e8a, $b4bc03eb07ed4 = true, $o275bd = false)
    {
        $A0c027a94ce654 = $this->getNodeInfo($Yc5381cbe4f46e8a);
        if( !$A0c027a94ce654 ) 
        {
            return false;
        }

        if( $o275bd ) 
        {
            $this->db->exec("UPDATE " . $this->table . " SET enabled=" . ($b4bc03eb07ed4 ? $A0c027a94ce654["enabled"] ? 1 : 0 : $A0c027a94ce654["enabled"] == "Y" ? "\"N\"" : "\"Y\"") . " WHERE numleft>=" . $A0c027a94ce654["numleft"] . " AND numright<=" . $A0c027a94ce654["numright"] . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce654[$this->groupField]) : ""));
        }
        else
        {
            $this->db->exec("UPDATE " . $this->table . " SET enabled = " . ($b4bc03eb07ed4 ? " (1-enabled) " : " (CASE WHEN (enabled=\"Y\") THEN \"N\" ELSE \"Y\" END) ") . " WHERE " . $this->id . " = :id " . ($this->useGroups ? " AND " . $this->groupField . " = " . $this->db->str2sql($A0c027a94ce654[$this->groupField]) : ""), array( ":id" => $Yc5381cbe4f46e8a ));
        }

        return true;
    }

    public function validate($d1da34f9c2b73af = true)
    {
        $A0c027a94ce655 = 0;
        $A0c027a94ce656 = array( "", "", "", "", "", "" );
        do
        {
            switch( $A0c027a94ce655 ) 
            {
                case 0:
                    $A0c027a94ce657 = $this->db->select_one_column("SELECT " . $this->id . " FROM " . $this->table . " WHERE numleft >= numright");
                    if( 0 < count($A0c027a94ce657) ) 
                    {
                        if( !$d1da34f9c2b73af ) 
                        {
                            return false;
                        }

                        $A0c027a94ce656[0] = "id: " . implode(", ", $A0c027a94ce657);
                    }

                    break;
                case 1:
                    $A0c027a94ce657 = $this->db->one_array("SELECT COUNT(" . $this->id . ") as n, MIN(numleft) as min_left,MIN(" . $this->id . ") as root_id, MAX(numright) as max_right FROM " . $this->table);
                    if( $A0c027a94ce657["min_left"] != 1 ) 
                    {
                        if( !$d1da34f9c2b73af ) 
                        {
                            return false;
                        }

                        $A0c027a94ce656[1] = "Наименьший левый ключ " . $A0c027a94ce657["min_left"] . " != 1<br/>(id: " . $A0c027a94ce657["root_id"] . ")";
                    }

                    if( $A0c027a94ce657["max_right"] != 2 * $A0c027a94ce657["n"] ) 
                    {
                        if( !$d1da34f9c2b73af ) 
                        {
                            return false;
                        }

                        $A0c027a94ce656[2] = "Наибольший правый ключ :" . $A0c027a94ce657["max_right"] . "  !=  " . 2 * $A0c027a94ce657["n"] . " <br/>(id: " . $A0c027a94ce657["root_id"] . ")";
                    }

                    break;
                case 2:
                    $A0c027a94ce657 = $this->db->select_one_column("SELECT mm." . $this->id . " FROM " . $this->table . " as mm, (SELECT " . $this->table . "." . $this->id . ",  MOD((numright - numleft) , 2) AS ostatok FROM " . $this->table . ") as m WHERE mm." . $this->id . " = m." . $this->id . " AND m.ostatok=0");
                    if( $A0c027a94ce657 ) 
                    {
                        if( !$d1da34f9c2b73af ) 
                        {
                            return false;
                        }

                        $A0c027a94ce656[3] = "id: " . implode(", ", $A0c027a94ce657);
                    }

                    break;
                case 3:
                    $A0c027a94ce657 = $this->db->select_one_column("SELECT mm." . $this->id . " FROM " . $this->table . " as mm, (SELECT " . $this->table . "." . $this->id . ", MOD( (" . $this->table . ".numleft - " . $this->table . ".numlevel + 1) , 2) AS ostatok FROM " . $this->table . ")as m WHERE mm." . $this->id . " = m." . $this->id . " AND m.ostatok = 1");
                    if( $A0c027a94ce657 ) 
                    {
                        if( !$d1da34f9c2b73af ) 
                        {
                            return false;
                        }

                        $A0c027a94ce656[4] = "id: " . implode(", ", $A0c027a94ce657);
                    }

                    break;
                case 4:
                    $A0c027a94ce657 = $this->db->one_array("SELECT t1." . $this->id . ", COUNT(t1." . $this->id . ") AS rep, MAX(t3.numright) AS max_right FROM " . $this->table . " AS t1, " . $this->table . " AS t2, " . $this->table . " AS t3 WHERE t1.numleft <> t2.numleft AND t1.numleft <> t2.numright AND t1.numright <> t2.numleft AND t1.numright <> t2.numright GROUP BY t1." . $this->id . " HAVING MAX(t3.numright) <> SQRT(4 * COUNT(t1." . $this->id . ") + 1) + 1");
                    if( $A0c027a94ce657 ) 
                    {
                        if( !$d1da34f9c2b73af ) 
                        {
                            return false;
                        }

                        foreach( $A0c027a94ce657 as $A0c027a94ce658 ) 
                        {
                            $A0c027a94ce656[5] .= "[id:" . $A0c027a94ce658[$this->id] . "  max_right:  " . $A0c027a94ce658["max_right"] . "]<br/>";
                        }
                    }

                    break;
            }
            $A0c027a94ce655++;
        }
        while( $A0c027a94ce655 < count($A0c027a94ce656) - 1 );
        if( $d1da34f9c2b73af ) 
        {
            return \View::renderTemplate($A0c027a94ce656, "validation", PATH_CORE . "db" . DS . "nestedsets");
        }

        return true;
    }

}



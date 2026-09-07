<?php
    namespace App\String;

    class LookupEvent 
    {
        public static function topicType(string $type): string {
            if ($type === "novote") {
                return 'เพื่อทราบ';
            } elseif ($type === "vote") {
                return 'เอกสารแนบ';
            } else {
                return 'ไม่ระบุประเภท';
            }
        }

        //vote_type enum('agree', 'disagree', 'novote', 'actual') เงื่อนไขการเทคะแนน agree = เห็นด้วย , disagree = ไม่เห็นด้วย , novote = งดออกเสียง , actual = นับคะแนนตามจริง	
        public static function voteType(string $type): string {
            if ($type === "agree") {
                return 'เห็นด้วย';
            } elseif ($type === "disagree") {
                return 'ไม่เห็นด้วย';
            } elseif ($type === "novote") {
                return 'งดออกเสียง';
            } elseif ($type === "actual") {
                return 'นับคะแนนตามจริง';
            } else {
                return 'ไม่ระบุเงื่อนไขการเทคะแนน';
            }
        }

        //stakeholder_vote_type enum('remove', 'abstention', 'cal', 'nocal') การคำนวณผู้มีส่วนได้เสีย remove = ตัดออก , adstention = งดออกเสียง , cal = ไม่มีสิทธ์ออกเสียง คำนวณร้อยละ , nocal = ไม่มีสิทธ์ออกเสียง ไม่คำนวณร้อยละ
        public static function stakeholderVoteType(string $type): string {
            if ($type === "remove") {
                return 'ตัดออก';
            } elseif ($type === "abstention") {
                return 'งดออกเสียง';
            } elseif ($type === "cal") {
                return 'ไม่มีสิทธ์ออกเสียง คำนวณร้อยละ';
            } elseif ($type === "nocal") {
                return 'ไม่มีสิทธ์ออกเสียง ไม่คำนวณร้อยละ';
            } else {
                return 'ไม่ระบุการคำนวณผู้มีส่วนได้เสีย';
            }
        }

        //resolution_cal enum('attendees_voter', 'attendees', 'total', 'actual') ฐานการคำนวณร้อยละ attendees_voter = เห็นด้วย + ไม่เห็นด้วย , attendees = งดออกเสียง + ไม่ลงคะแนน , total = หุ้นทั้งหมด , actual = ลงคะแนนทั้งหมด
        public static function resolutionCal(string $type): string {
            if ($type === "attendees_voter") {
                return 'เห็นด้วย + ไม่เห็นด้วย';
            } elseif ($type === "attendees") {
                return 'งดออกเสียง + ไม่ลงคะแนน';
            } elseif ($type === "total") {
                return 'หุ้นทั้งหมด';
            } elseif ($type === "actual") {
                return 'ลงคะแนนทั้งหมด';
            } else {
                return 'ไม่ระบุฐานการคำนวณร้อยละ';
            }
        }

        //invalid_display enum('hidden', 'calpercent', 'nocalpercent') 	แสดงบัตรเสีย hidden = ไม่แสดง , calpercent = แสดงและคำนวณร้อยละ , nocalpercent = แสดงและไม่คำนวณร้อยละ	
        public static function invalidDisplay(string $type): string {
            if ($type === "hidden") {
                return 'ไม่แสดง';
            } elseif ($type === "calpercent") {
                return 'แสดงและคำนวณร้อยละ';
            } elseif ($type === "nocalpercent") {
                return 'แสดงและไม่คำนวณร้อยละ';
            } else {
                return 'ไม่ระบุการแสดงบัตรเสีย';
            }
        }

    }

?>
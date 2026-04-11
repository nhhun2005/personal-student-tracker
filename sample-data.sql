
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `student_id`, `email`, `reset_token`, `reset_expire`) VALUES
(1, 'nhhun2005', 'huanlm123', 'Nguyễn Hà Huân', 'B2303869', 'nhhun2005@gmail.com', NULL, NULL);

-- ADD this to not crash the tpoint page
INSERT INTO `semesters` (`id`, `semester_name`, `user_id`) VALUES
(1, 'HK2 2025-2026', 1),
(2, 'HK3 2025-2026', 1),
(3, 'HK1 2026-2027', 1),
(4, 'HK2 2026-2027', 1),
(5, 'HK3 2026-2027', 1);


INSERT INTO `courses` (`id`, `course_name`, `credits`, `score`, `semester_id`) VALUES
(1, 'Nhập môn công nghệ phần mềm', 2, 4.00, 1),
(2, 'Lập trình căn bản A', 4, 4.00, 1),
(3, 'Kinh tế chính trị Mác-Lênin', 2, 3.00, 1),
(4, 'Toán cho KHMT', 4, 3.00, 2),
(5, 'Anh văn tăng cường', 1, 2.00, 3),
(6, 'Lập trình Web', 3, 4.00, 4),
(7, 'Xác suất thống kê', 2, 4.00, 5);

-- ADD this to not crash the tpoint page
INSERT INTO `criterions` (`id`, `criterion_name`, `max_score`, `type`) VALUES
(1, 'I.a Ý thức và thái độ trong học tập', 6, 'I'),
(2, 'I.b Ý thức và thái độ tham gia các câu lạc bộ học thuật, NCKH', 10, 'I'),
(3, 'I.c Ý thức và thái độ tham gia các kỳ thi, cuộc thi', 6, 'I'),
(4, 'I.d Tinh thần vượt khó, phấn đấu vươn lên trong học tập', 2, 'I'),
(5, 'I.e Kết quả học tập', 8, 'I'),
(6, 'II.a Ý thức chấp hành các văn bản chỉ đạo của ngành, cơ quan cấp trên', 15, 'II'),
(7, 'II.b Ý thức chấp hành các nội quy, quy chế nhà trường', 10, 'II'),
(8, 'III.a Ý thức và hiệu quả tham gia hoạt động chính trị, văn hóa, thể thao', 15, 'III'),
(9, 'III.b Ý thức tham gia các hoạt động công ích tình nguyện', 5, 'III'),
(10, 'III.c Tham gia tuyên truyền, phòng chống tội phạm và tệ nạn xã hội', 10, 'III'),
(11, 'IV.a Ý thức chấp hành và tuyên truyền chủ trương của Đảng, pháp luật Nhà nước', 15, 'IV'),
(12, 'IV.b Ý thức tham gia hoạt động xã hội có thành tích được ghi nhận', 10, 'IV'),
(13, 'IV.c Tinh thần chia sẻ, giúp đỡ người thân, người có khó khăn', 5, 'IV'),
(14, 'V.a Ý thức, tinh thần và hiệu quả của cán bộ lớp, đoàn thể', 10, 'V'),
(15, 'V.b Kỹ năng tổ chức, quản lý lớp và các tổ chức trong nhà trường', 9, 'V'),
(16, 'V.c Hỗ trợ và tham gia tích cực vào các hoạt động chung của tập thể', 8, 'V'),
(17, 'V.d Người học đạt được các thành tích đặc biệt trong học tập, rèn luyện', 8, 'V');


INSERT INTO `evidences` (`id`, `content`, `score_value`, `event_date`, `criterion_id`, `user_id`, `semester_id`) VALUES
(6, 0x6d696e686368756e67, 6.00, '2026-04-11', 1, 1, 1),
(7, NULL, 5.00, '2026-04-11', 2, 1, 1),
(8, NULL, 10.00, '2026-04-11', 2, 1, 1),
(9, NULL, 15.00, '2026-04-11', 3, 1, 1),
(10, NULL, 2.00, '2026-04-11', 6, 1, 1),
(11, NULL, 5.00, '2026-04-11', 6, 1, 1),
(12, NULL, 14.00, '2026-04-11', 6, 1, 1);
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 05:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dronacharya`
--

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` varchar(255) NOT NULL,
  `lesson_title` varchar(500) NOT NULL,
  `lesson_content` text DEFAULT NULL,
  `ai_notes` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `course_id`, `lesson_title`, `lesson_content`, `ai_notes`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'course_1758319297_68cdd2c1a3629', 'Hands-on Practice', 'Learn about Hands-on Practice', '### Hands-on Practice\r\n\r\nHands-on practice is a **learning approach that emphasizes doing rather than only reading or listening**. It allows learners to apply theoretical knowledge directly in real-world or simulated tasks, strengthening both understanding and skill mastery.\r\n\r\n#### Why Hands-on Practice Matters\r\n\r\n1. **Active Learning** – Engages the learner fully, making knowledge retention stronger than passive study methods.\r\n2. **Skill Development** – Builds confidence by applying concepts to practical problems.\r\n3. **Error-Based Learning** – Mistakes made during practice create opportunities for correction and deeper understanding.\r\n4. **Problem-Solving** – Encourages critical thinking by facing real challenges.\r\n\r\n#### Examples of Hands-on Practice\r\n\r\n* In **programming**, writing code, debugging, and building small projects.\r\n* In **science**, conducting experiments and observing outcomes.\r\n* In **languages**, speaking, writing, and conversing with native speakers.\r\n\r\n#### Benefits\r\n\r\n* Bridges the gap between theory and practice.\r\n* Prepares learners for real-world applications and jobs.\r\n* Increases confidence and independence in applying knowledge.\r\n\r\n#### How to Apply\r\n\r\n* Start small: practice after each concept learned.\r\n* Use projects, exercises, or case studies.\r\n* Continuously reflect and improve through feedback.\r\n\r\n👉 Hands-on practice transforms knowledge into **practical skills**, making learning effective and long-lasting.\r\n', NULL, '2025-09-19 22:20:38', '2025-09-19 22:20:38'),
(2, 3, 'course_1758321893_68cddce5b0f5a', 'Introduction to python', 'Learn about Introduction to python', '### Introduction to Python\r\n\r\nPython is a **high-level, interpreted programming language** widely used for software development, data science, web applications, automation, and artificial intelligence. Known for its **simplicity and readability**, Python allows beginners and professionals to focus on solving problems rather than struggling with complex syntax.\r\n\r\n#### Key Features\r\n\r\n1. **Readable Syntax** – Python code resembles everyday English, making it beginner-friendly.\r\n2. **Interpreted Language** – Code is executed line by line, simplifying testing and debugging.\r\n3. **Cross-Platform** – Works on Windows, macOS, and Linux without major changes.\r\n4. **Rich Libraries** – Offers built-in modules and external libraries for diverse applications like NumPy (data), Flask (web), and TensorFlow (AI).\r\n5. **Community Support** – Python has a large, active community and extensive documentation.\r\n\r\n#### Why Learn Python?\r\n\r\n* Versatile: used in web development, machine learning, data analysis, automation, and more.\r\n* Beginner-friendly: an excellent first language to learn programming concepts.\r\n* Industry demand: highly valued skill in tech careers.\r\n\r\n#### Example Code\r\n\r\n```python\r\nprint(\"Hello, Python!\")\r\n```\r\n\r\n👉 In summary, Python is a **powerful yet easy-to-learn language**, making it an ideal starting point for anyone entering the world of programming.\r\n', NULL, '2025-09-19 22:46:09', '2025-09-19 22:46:09'),
(3, 3, 'course_1758322075_68cddd9b3edc4', 'Introduction to learn german language', 'Learn about Introduction to learn german language', '**Introduction to Learning the German Language**\r\n\r\nGerman is one of the most widely spoken languages in Europe, with over 90 million native speakers, mainly in Germany, Austria, and parts of Switzerland. It is also an important language in business, science, philosophy, and literature. Learning German not only opens opportunities for communication but also provides access to rich cultural and academic resources.\r\n\r\nGerman belongs to the West Germanic branch of the Indo-European language family, sharing similarities with English and Dutch. Its grammar involves cases (nominative, accusative, dative, genitive), genders (masculine, feminine, neuter), and verb conjugations that may seem complex at first but follow clear rules. Vocabulary often overlaps with English, making it easier to recognize words.\r\n\r\nLearning German requires building a foundation in basic greetings, numbers, and everyday phrases. From there, one should progress to grammar, sentence structure, and vocabulary expansion. Practicing listening, speaking, reading, and writing regularly accelerates fluency. Immersing yourself through movies, music, or conversations with native speakers enhances real-world understanding.\r\n\r\nGerman learning resources include textbooks, mobile apps (Duolingo, Babbel), and language exchange communities. With consistent practice, learners can achieve proficiency and gain both personal and professional advantages.\r\n', NULL, '2025-09-19 22:49:36', '2025-09-19 22:49:36'),
(4, 3, 'course_1758322075_68cddd9b3edc4', 'Basic Concepts', 'Learn about Basic Concepts', '### Basic Concepts\r\n\r\nBasic concepts form the foundation for understanding any subject, whether it’s programming, science, or mathematics. They provide the <b>fundamental principles and building blocks</b> upon which more complex ideas are developed. A strong grasp of basic concepts ensures better comprehension and problem-solving skills.\r\n\r\n#### Key Elements in Programming\r\n\r\n1. <strong>Variables</strong> – Store data values that can be used and modified throughout a program.\r\n2. <b>Data Types</b> – Define the kind of data, such as numbers, text, boolean, or lists.\r\n3. <b>Operators</b> – Symbols that perform operations on variables and values, like arithmetic (+, -, \\*, /), comparison (>, <, ==), and logical operators (and, or, not).\r\n4. <b>Control Flow</b> – Directs the execution of code using conditional statements (`if`, `else`) and loops (`for`, `while`).\r\n5. <b>Functions</b> – Encapsulate reusable blocks of code to perform specific tasks.\r\n\r\n#### Importance\r\n\r\n* Helps learners understand advanced topics easily.\r\n* Reduces confusion by clarifying core principles.\r\n* Encourages logical thinking and systematic problem-solving.\r\n\r\n#### Application\r\n\r\nIn practice, mastering basic concepts allows you to write simple programs, analyze problems, and gradually progress to complex projects. Continuous practice and experimentation are essential to internalize these fundamentals.\r\n\r\n👉 In summary, basic concepts are the <b>cornerstone of learning</b> and serve as a bridge to advanced knowledge and skills.\r\n', NULL, '2025-09-19 22:57:45', '2025-09-19 23:04:06'),
(5, 1, 'course_1758325047_68cde937bb6b2', 'Introduction to maths on diffrenciation', 'Learn about Introduction to maths on diffrenciation', '### Introduction to Differentiation\r\n\r\nDifferentiation is a fundamental concept in <b>calculus</b>, which deals with the <b>rate at which a quantity changes</b>. It is a mathematical tool used to find the <b>derivative</b> of a function, representing the slope of the tangent line at any point on a curve. Differentiation is widely applied in physics, engineering, economics, and other fields to analyze change and optimize solutions.\r\n\r\n#### Key Concepts\r\n\r\n1. <b>Derivative</b> – The derivative of a function $f(x)$ with respect to $x$ measures how $f(x)$ changes as $x$ changes. It is denoted as $f\'(x)$ or $\\frac{dy}{dx}$.\r\n2. <b>Basic Rules</b> – Includes the power rule, constant rule, sum and difference rules, which simplify finding derivatives of simple functions.\r\n3. <b>Product and Quotient Rules</b> – Allow differentiation of products and ratios of functions.\r\n4. <b>Chain Rule</b> – Used to differentiate composite functions.\r\n\r\n#### Applications\r\n\r\n* <b>Slope of a curve</b> – Determines steepness at a specific point.\r\n* <b>Optimization</b> – Helps find maximum and minimum values of functions.\r\n* <b>Motion analysis</b> – Velocity and acceleration are derivatives of position with respect to time.\r\n* <b>Economics</b> – Measures marginal cost, revenue, and profit changes.\r\n\r\n#### Conclusion\r\n\r\nUnderstanding differentiation provides a <b>powerful method for analyzing change</b> and solving real-world problems involving rates, slopes, and optimization. Mastery of basic rules and applications is essential for progressing in calculus.\r\n', NULL, '2025-09-19 23:38:49', '2025-09-19 23:38:49'),
(6, 4, 'course_1758333961_68ce0c0964d2f', 'Basic Concepts', 'Learn about Basic Concepts', '### Study Notes: Basic Concepts\r\n\r\n<b>Introduction</b>\r\nBasic concepts are the fundamental building blocks of understanding in any subject. They provide the foundation upon which advanced ideas and applications are developed. Grasping these core principles ensures clarity and prevents confusion when dealing with complex topics.\r\n\r\n<b>Definition</b>\r\nA *basic concept* refers to a simple, essential idea that is universally applicable and easily understandable. For example, in mathematics, numbers are a basic concept; in science, matter and energy are basic concepts; in language, words and grammar form the base.\r\n\r\n<b>Importance</b>\r\n\r\n1. <b>Foundation of Learning</b> – Without a solid grasp of basics, advanced topics become difficult.\r\n2. <b>Problem-Solving</b> – Core principles guide reasoning and solutions.\r\n3. <b>Transferability</b> – Basic ideas apply across subjects and real-life situations.\r\n\r\n<b>Characteristics of Basic Concepts</b>\r\n\r\n* Simple and fundamental.\r\n* Easy to recognize and categorize.\r\n* Often linked to daily experiences.\r\n* Serve as stepping stones for advanced knowledge.\r\n\r\n<b>Examples Across Fields</b>\r\n\r\n* <b>Mathematics</b>: Numbers, operations.\r\n* <b>Science</b>: Atoms, force, energy.\r\n* <b>Computer Science</b>: Data, algorithms.\r\n* <b>Language</b>: Alphabets, grammar rules.\r\n\r\n<b>Conclusion</b>\r\nMastering basic concepts is essential for lifelong learning. They act as the roots of knowledge, ensuring growth and understanding in every discipline.\r\n', 'Got it 👍 You want me to enhance the <b>“Basic Concepts” study notes</b> with <b>diagrams, charts, or visual elements</b> to make it more professional and educational.\r\n', '2025-09-20 02:11:08', '2025-09-20 02:11:08'),
(7, 1, 'course_1758318264_68cdceb8336d4', 'Basic Concepts', 'Learn about Basic Concepts', 'Educational illustration about: Basic Concepts. Create a detailed, informative, and visually appealing image that represents this learning topic. Include relevant diagrams, charts, or visual elements that would help students understand the concept. Make it suitable for educational content with clear, professional design.', '<b>Study Notes: Basic Concepts</b>\r\n\r\nBasic concepts form the foundation of understanding any subject. They provide the essential knowledge needed to build advanced ideas and solve real-world problems. A concept is essentially a general idea or principle that explains how things work or relate to each other.\r\n\r\n1. <b>Definition</b>: A concept is an abstract representation of reality that helps us classify and organize information.\r\n2. <b>Importance</b>: Learning basic concepts simplifies complex', '2025-09-20 02:19:52', '2025-09-20 02:19:52'),
(8, 1, 'course_1758318264_68cdceb8336d4', 'Introduction to dsa', 'Learn about Introduction to dsa', 'Here’s a concise, well-structured set of study notes for <b>Introduction to DSA</b> within 200 words:\r\n\r\n---\r\n\r\n# <b>Introduction to Data Structures and Algorithms (DSA)</b>\r\n\r\n<b>Definition:</b>\r\nDSA is the study of organizing data efficiently (Data Structures) and solving problems effectively (Algorithms). Together, they form the backbone of programming and software development.\r\n\r\n---\r\n\r\n## <b>1. Importance of DSA</b>\r\n\r\n* Optimizes memory and processing time.\r\n* Essential for problem-solving in coding, competitive programming, and system design.\r\n* Helps in writing efficient, scalable software.\r\n\r\n---\r\n\r\n## <b>2. Data Structures</b>\r\n\r\nData structures store and organize data for efficient access and modification.\r\n<b>Types:</b>\r\n\r\n* <b>Linear:</b> Elements in sequence. Examples: Arrays, Linked Lists, Stacks, Queues.\r\n* <b>Non-linear:</b> Hierarchical or networked. Examples: Trees, Graphs.\r\n* <b>Hash-based:</b> Key-value mapping. Example: Hash Tables.\r\n\r\n<b>Key operations:</b> Insert, Delete, Update, Search, Traverse.\r\n\r\n---\r\n\r\n## <b>3. Algorithms</b>\r\n\r\nAlgorithms are step-by-step procedures to solve a problem.\r\n<b>Types:</b>\r\n\r\n* <b>Sorting:</b> Bubble, Quick, Merge Sort.\r\n* <b>Searching:</b> Linear, Binary Search.\r\n* <b>Graph algorithms:</b> BFS, DFS, Dijkstra’s Algorithm.\r\n* <b>Dynamic Programming:</b> Solving complex problems via subproblems.\r\n\r\n<b>Complexity Analysis:</b>\r\n\r\n* <b>Time Complexity:</b> Execution time vs input size.\r\n* <b>Space Complexity:</b> Memory usage vs input size.\r\n  Big O notation measures efficiency.\r\n\r\n---\r\n\r\n<b>Conclusion:</b>\r\nMastering DSA is critical for writing optimized code, tackling real-world problems, and excelling in programming interviews. Begin with simple data structures, understand algorithms, and gradually move to complex problems.\r\n\r\n---\r\n\r\nIf you want, I can also make a <b>visual summary diagram of DSA</b> to make it even easier to memorize. Do you want me to create that?\r\n', NULL, '2025-09-20 02:23:26', '2025-09-20 02:42:24'),
(9, 1, 'course_1758336630_68ce16765f053', 'Introduction to machine learning', 'Learn about Introduction to machine learning', '<b>Introduction to Machine Learning (ML) – Study Notes</b>\r\n\r\n<b>Definition:</b>\r\nMachine Learning (ML) is a subset of Artificial Intelligence (AI) that enables systems to learn from data and improve performance over time without being explicitly programmed. Instead of following fixed rules, ML algorithms identify patterns and make predictions or decisions based on input data.\r\n\r\n<b>Key Concepts:</b>\r\n\r\n1. <b>Data:</b> The foundation of ML; can be structured (tables) or unstructured (images, text).\r\n2. <b>Features:</b> Individual measurable properties or characteristics of data used for predictions.\r\n3. <b>Labels:</b> Output values that a model is trained to predict (in supervised learning).\r\n4. <b>Training and Testing:</b> Training uses historical data to build a model; testing evaluates its performance on unseen data.\r\n5. <b>Overfitting & Underfitting:</b> Overfitting occurs when a model learns noise in data; underfitting occurs when it fails to capture patterns.\r\n\r\n<b>Types of Machine Learning:</b>\r\n\r\n* <b>Supervised Learning:</b> Learns from labeled data (e.g., predicting house prices).\r\n* <b>Unsupervised Learning:</b> Finds patterns in unlabeled data (e.g., customer segmentation).\r\n* <b>Reinforcement Learning:</b> Learns via feedback from actions and rewards (e.g., game-playing AI).\r\n\r\n<b>Applications:</b>\r\nML powers recommendation systems, spam detection, self-driving cars, medical diagnosis, and fraud detection.\r\n\r\n<b>Conclusion:</b>\r\nMachine Learning transforms raw data into actionable insights. Understanding its fundamentals, types, and workflow is crucial for applying it effectively across industries.\r\n', NULL, '2025-09-20 02:52:21', '2025-09-20 02:52:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Akash', 'akashb1152004@gmail.com', '$2y$10$FVIpKGlbadijMcQXS9bcNuPImWT2bYagseo0Wvct0TLAFnT4SQMx6', '2025-09-19 18:15:29', '2025-09-19 18:15:29'),
(2, 'abc', 'abc@gmail.com', '$2y$10$1FAYQBmtdLAywJZo04GWwucRT7vDYiqrhLCdVO4Z5IWGt8L4ziaFi', '2025-09-19 22:09:45', '2025-09-19 22:09:45'),
(3, 'varun', 'hi@gmail.com', '$2y$10$3a0p.iAhIlZu.pO.pDj13uittsal7qC8KFu0E8OgHPCJR/adTmEB.', '2025-09-19 22:44:32', '2025-09-19 22:44:32'),
(4, 'pavi', 'p@g.com', '$2y$10$v3YM0nr3RqN68AAbMKaI2.AbFa/oeWOEHCUNivEATc6UEpYropWPa', '2025-09-20 02:02:12', '2025-09-20 02:02:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

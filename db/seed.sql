INSERT INTO grupos (nombre, descripcion) VALUES
('Group1', 'Jugadores de desarrollo y análisis'),
('Group2', 'Jugadores del grupo comunitario');

INSERT INTO rank_history (fecha, jugador, tier, division, lp, wins, losses, grupo_id) VALUES
-- Grupo 1: Colmena
('2025-10-20', 'Jugador1', 'IRON', 'IV', 15, 2, 5, 1),
('2025-10-22', 'Jugador1', 'IRON', 'IV', 38, 4, 7, 1),
('2025-10-25', 'Jugador1', 'IRON', 'III', 55, 6, 9, 1),
('2025-10-28', 'Jugador1', 'IRON', 'III', 63, 7, 12, 1),
('2025-10-30', 'Jugador1', 'IRON', 'III', 65, 7, 17, 1),
('2025-11-02', 'Jugador1', 'IRON', 'II', 85, 10, 18, 1),
('2025-11-05', 'Jugador1', 'IRON', 'I', 95, 12, 19, 1),

('2025-10-21', 'Jugador2', 'IRON', 'IV', 20, 10, 22, 1),
('2025-10-23', 'Jugador2', 'IRON', 'IV', 40, 18, 27, 1),
('2025-10-26', 'Jugador2', 'IRON', 'III', 55, 25, 35, 1),
('2025-10-29', 'Jugador2', 'IRON', 'III', 78, 38, 45, 1),
('2025-10-31', 'Jugador2', 'IRON', 'IV', 94, 52, 103, 1),
('2025-11-03', 'Jugador2', 'BRONZE', 'IV', 10, 60, 110, 1),
('2025-11-06', 'Jugador2', 'BRONZE', 'III', 45, 70, 120, 1),

-- Grupo 2: BronzeCode
('2025-10-19', 'Jugador3', 'BRONZE', 'IV', 10, 80, 95, 2),
('2025-10-21', 'Jugador3', 'BRONZE', 'IV', 25, 90, 100, 2),
('2025-10-24', 'Jugador3', 'SILVER', 'IV', 50, 98, 105, 2),
('2025-10-28', 'Jugador3', 'SILVER', 'IV', 64, 101, 108, 2),
('2025-11-01', 'Jugador3', 'SILVER', 'IV', 68, 102, 110, 2),
('2025-11-05', 'Jugador3', 'SILVER', 'IV', 72, 105, 112, 2),
('2025-11-07', 'Jugador3', 'SILVER', 'III', 90, 108, 115, 2),

('2025-10-20', 'Jugador4', 'BRONZE', 'IV', 15, 30, 45, 2),
('2025-10-22', 'Jugador4', 'BRONZE', 'III', 35, 36, 50, 2),
('2025-10-25', 'Jugador4', 'BRONZE', 'II', 50, 40, 52, 2),
('2025-10-29', 'Jugador4', 'BRONZE', 'I', 70, 46, 55, 2),
('2025-11-02', 'Jugador4', 'SILVER', 'IV', 10, 50, 60, 2),
('2025-11-05', 'Jugador4', 'SILVER', 'IV', 25, 55, 65, 2),
('2025-11-08', 'Jugador4', 'SILVER', 'III', 42, 58, 68, 2);

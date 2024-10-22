CREATE TABLE flex_test (
    id SERIAL PRIMARY KEY,
    signdate TIMESTAMP NOT NULL,
    title VARCHAR(45) NOT NULL
);

COMMENT ON TABLE flex_test IS '테스트 테이블';
COMMENT ON COLUMN flex_test.id IS '고유번호';
COMMENT ON COLUMN flex_test.signdate IS '등록일';
COMMENT ON COLUMN flex_test.title IS '제목';